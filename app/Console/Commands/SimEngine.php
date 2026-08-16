<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * sim:engine — the smart, script-driven activity engine (Meow TEST only).
 *
 * A long-running daemon that WATCHES a scripts inbox (storage/sim/scripts/*.json).
 * Drop a scenario in → the engine picks it up, runs it (on existing user ids OR
 * freshly-created test users), and archives the script to scripts/done/ when its
 * duration ends. Many scripts run together; you never delete/replace old ones —
 * you just keep dropping new scenarios. Every action is fully logged per user
 * (request + response + latency) in storage/logs/sim/user_{id}.log.
 *
 * Script schema (JSON):
 * {
 *   "name": "lucky-storm-9",
 *   "users": { "mode": "existing", "ids": [3,4,5] },   // or {"mode":"create","count":20}
 *   "duration_minutes": 30,
 *   "room_ids": [9, 11],                                // optional; default = all audio rooms
 *   "actions": ["enter","mic_up","gift","lucky","chat","leave"],
 *   "subset": 20                                        // users acting per tick (optional)
 * }
 *
 * TEST-SERVER ONLY. Gated by settings.json flag sim_engine_enabled (0 to pause).
 */
class SimEngine extends Command
{
    protected $signature = 'sim:engine {--poll=5 : seconds between ticks} {--once : run one tick then exit (for testing)}';
    protected $description = 'Script-driven activity engine for the test accounts (Meow test only).';

    private const SLOW_MS = 1000;
    private string $base = 'http://127.0.0.1:8099/api';
    private array $tokens = [];        // userId => token
    private array $active = [];        // scenarioName => scenario state
    private array $lastBanner = [];    // roomId => microtime (banner spacing)

    public function handle(): int
    {
        $dir = storage_path('sim/scripts');
        @mkdir($dir . '/done', 0775, true);
        $poll = max(1, (int) $this->option('poll'));
        $this->loadTokens(); // reuse tokens across restarts (avoid checkLatestToken churn)
        $this->info('sim:engine watching ' . $dir . ' (cached tokens=' . count($this->tokens) . ')');

        do {
            if (((int) (settings()->get('sim_engine_enabled') ?? 1)) === 1) {
                $this->loadNewScripts($dir);
                $this->tickAll();
                $this->reapExpired($dir);
            }
            if (!$this->option('once')) {
                sleep($poll);
            }
        } while (!$this->option('once'));

        return self::SUCCESS;
    }

    private function loadNewScripts(string $dir): void
    {
        foreach (glob($dir . '/*.json') as $file) {
            $name = basename($file, '.json');
            if (isset($this->active[$name])) {
                continue;
            }
            $spec = json_decode(@file_get_contents($file), true);
            if (!is_array($spec)) {
                $this->warn("bad script: $name");
                @rename($file, $dir . '/done/' . $name . '.bad.json');
                continue;
            }
            $users = $this->resolveUsers($spec['users'] ?? []);
            $minted = false;
            foreach ($users as $uid) {
                if (!isset($this->tokens[$uid]) && ($u = User::find($uid))) {
                    // ONE token per user, reused across restarts (persisted) so we
                    // never invalidate a prior session via checkLatestToken.
                    $this->tokens[$uid] = $u->createToken('sim')->plainTextToken;
                    $minted = true;
                }
            }
            if ($minted) {
                $this->saveTokens();
            }
            $rooms = !empty($spec['room_ids'])
                ? DB::table('rooms')->whereIn('id', $spec['room_ids'])->get(['id', 'uid'])->all()
                : DB::table('rooms')->where('type', 'audio')->get(['id', 'uid'])->all();
            $this->active[$name] = [
                'file'    => $file,
                'users'   => array_values(array_filter($users, fn ($u) => isset($this->tokens[$u]))),
                'rooms'   => array_map(fn ($r) => ['id' => (int) $r->id, 'owner' => (int) $r->uid], $rooms),
                'actions' => $spec['actions'] ?? ['enter', 'mic_up', 'gift', 'lucky', 'chat', 'leave'],
                'subset'  => max(1, (int) ($spec['subset'] ?? 20)),
                'until'   => time() + ((int) ($spec['duration_minutes'] ?? 30)) * 60,
                'state'   => [],
            ];
            $this->info("loaded script '$name': users=" . count($this->active[$name]['users']) . " rooms=" . count($rooms) . " until=+" . ($spec['duration_minutes'] ?? 30) . "m");
        }
    }

    /** existing ids, or create N fresh test users with a coin balance. */
    private function resolveUsers(array $u): array
    {
        $mode = $u['mode'] ?? 'existing';
        if ($mode === 'create') {
            $ids = [];
            $n = max(1, (int) ($u['count'] ?? 5));
            $bal = (int) ($u['balance'] ?? 1_000_000_000);
            for ($i = 0; $i < $n; $i++) {
                $nu = User::create([
                    'name'              => 'SimBot_' . Str::random(6),
                    'email'             => strtolower(Str::random(10)) . '@sim.local',
                    'password'          => bcrypt('password'),
                    'di'                => $bal,
                    'email_verified_at' => now(),
                ]);
                $ids[] = $nu->id;
            }
            $this->info('created ' . count($ids) . ' sim users');
            return $ids;
        }
        return array_map('intval', $u['ids'] ?? []);
    }

    private function tickAll(): void
    {
        foreach ($this->active as $name => &$sc) {
            if (empty($sc['users']) || empty($sc['rooms'])) {
                continue;
            }
            $actors = collect($sc['users'])->shuffle()->take($sc['subset']);
            foreach ($actors as $uid) {
                try {
                    $this->actOne($uid, $sc);
                } catch (\Throwable $e) {
                    $this->logUser($uid, 'EXCEPTION', [], ['ok' => false, 'status' => 0, 'ms' => 0, 'body' => $e->getMessage() . ' @ ' . $e->getLine()]);
                }
                usleep(random_int(40000, 160000));
            }
            if (!empty($sc['roles'])) {
                try {
                    $this->moderate($name, $sc);
                } catch (\Throwable $e) {
                    $this->logUser((int) ($sc['roles']['owner'] ?? 0), 'EXCEPTION_MOD', [], ['ok' => false, 'status' => 0, 'ms' => 0, 'body' => $e->getMessage()]);
                }
            }
        }
        unset($sc);
    }

    private function actOne(int $uid, array &$sc): void
    {
        $st = $sc['state'][$uid] ?? null;
        $allowed = $sc['actions'];

        if (!$st) {
            if (in_array('enter', $allowed, true)) {
                $room = $sc['rooms'][array_rand($sc['rooms'])];
                $r = $this->post($uid, '/rooms/enter_room', ['room_id' => $room['id'], 'owner_id' => $room['owner']]);
                if ($r['ok']) {
                    $sc['state'][$uid] = ['room' => $room['id'], 'owner' => $room['owner'], 'seat' => null];
                }
            }
            return;
        }

        $menu = [];
        foreach ($allowed as $a) {
            if ($a === 'mic_up' && $st['seat'] !== null) continue;
            if ($a === 'mic_down' && $st['seat'] === null) continue;
            if ($a === 'enter') continue;
            $menu[] = $a;
        }
        if (empty($menu)) { return; }
        $pick = $menu[array_rand($menu)];

        switch ($pick) {
            case 'gift':
                $g = DB::table('gifts')->where('type', '!=', 6)->where('enable', 1)->where('vip_level', 0)->where('is_play', 0)->inRandomOrder()->value('id');
                if ($g && $st['owner'] != $uid) {
                    $this->post($uid, '/gifts/send', ['id' => $g, 'toUid' => (string) $st['owner'], 'num' => 1, 'owner_id' => $st['owner'], 'room_id' => $st['room'], 'type' => 1]);
                }
                break;
            case 'lucky':
                $g = DB::table('gifts')->where('type', 6)->where('enable', 1)->inRandomOrder()->value('id');
                if ($g && $st['owner'] != $uid) {
                    $this->post($uid, '/gifts/v2/send-lucky-gift-combo', ['id' => $g, 'toUid' => (string) $st['owner'], 'num' => 1, 'count' => 1, 'owner_id' => $st['owner'], 'room_id' => $st['room'], 'nonce' => (string) Str::uuid()]);
                }
                break;
            case 'mic_up':
                $pos = random_int(1, 8);
                $r = $this->post($uid, '/rooms/up-microphone', ['user_id' => $uid, 'room_id' => $st['room'], 'owner_id' => $st['owner'], 'position' => $pos]);
                if ($r['ok']) { $sc['state'][$uid]['seat'] = $pos; }
                break;
            case 'mic_down':
                $r = $this->post($uid, '/rooms/leave-microphone', ['user_id' => $uid, 'room_id' => $st['room'], 'owner_id' => $st['owner'], 'position' => $st['seat']]);
                if ($r['ok']) { $sc['state'][$uid]['seat'] = null; }
                break;
            case 'chat':
                $now = microtime(true);
                if ($now - ($this->lastBanner[$st['room']] ?? 0) < 10.0) { break; }
                $this->lastBanner[$st['room']] = $now;
                $msgs = ['أهلاً بالكل 👋', 'نورتوا الغرفة', 'الجو حلو هنا 🔥', 'منوّر يا نجم', 'يلا نشّطوا'];
                $this->post($uid, '/rooms/yellow-banner', ['room_id' => $st['room'], 'owner_id' => $st['owner'], 'message' => $msgs[array_rand($msgs)]]);
                break;
            case 'follow':
                if ($st['owner'] != $uid) {
                    $this->post($uid, '/relations/follow', ['id' => $st['owner'], 'user_id' => $st['owner']]);
                }
                break;
            case 'leave':
                $this->post($uid, '/rooms/quit_room', ['room_id' => $st['room'], 'owner_id' => $st['owner']]);
                unset($sc['state'][$uid]);
                break;
        }
    }

    private array $modStep = [];

    /**
     * Deterministic moderation pass driven by a script "roles" block, e.g.
     *   "roles": { "owner": 3, "room_id": 9, "admin": 4, "kick": 5, "ban": 6 }
     * The owner enters their room then periodically promotes/kicks/bans the named
     * targets — exercising the admin/kick/ban flows end-to-end (all fully logged).
     */
    private function moderate(string $name, array &$sc): void
    {
        $roles = $sc['roles'] ?? [];
        $owner = (int) ($roles['owner'] ?? 0);
        $room  = (int) ($roles['room_id'] ?? 0);
        if (!$owner || !$room || !isset($this->tokens[$owner])) {
            return;
        }
        if (($sc['state'][$owner]['room'] ?? null) !== $room) {
            $r = $this->post($owner, '/rooms/enter_room', ['room_id' => $room, 'owner_id' => $owner]);
            if ($r['ok']) {
                $sc['state'][$owner] = ['room' => $room, 'owner' => $owner, 'seat' => null];
            }
        }
        $now = microtime(true);
        if ($now - ($this->modStep[$name] ?? 0) < 20.0) {
            return;
        }
        $this->modStep[$name] = $now;
        $pick = ['admin', 'kick', 'ban'][array_rand(['admin', 'kick', 'ban'])];
        $target = (int) ($roles[$pick] ?? 0);
        if (!$target) {
            return;
        }
        match ($pick) {
            'admin' => $this->post($owner, '/rooms/add_admin_to_room', ['owner_id' => $owner, 'room_id' => $room, 'user_id' => $target]),
            'kick'  => $this->post($owner, '/rooms/kick_out_of_room', ['owner_id' => $owner, 'room_id' => $room, 'user_id' => $target, 'minutes' => 5]),
            'ban'   => $this->post($owner, '/rooms/black-list', ['owner_id' => $owner, 'room_id' => $room, 'user_id' => $target, 'minutes' => 10]),
        };
    }

    private function reapExpired(string $dir): void
    {
        foreach ($this->active as $name => $sc) {
            if (time() >= $sc['until']) {
                @rename($sc['file'], $dir . '/done/' . $name . '.' . date('Ymd_His') . '.json');
                unset($this->active[$name]);
                $this->info("archived script '$name'");
            }
        }
    }

    private function post(int $uid, string $path, array $data): array
    {
        $t0 = microtime(true);
        try {
            $res = Http::withToken($this->tokens[$uid])->acceptJson()->timeout(15)->asForm()->post($this->base . $path, $data);
            $out = ['ok' => $res->successful(), 'status' => $res->status(), 'body' => $res->body()];
        } catch (\Throwable $e) {
            $out = ['ok' => false, 'status' => 0, 'body' => 'EXCEPTION: ' . $e->getMessage()];
        }
        $out['ms'] = (int) round((microtime(true) - $t0) * 1000);
        $this->logUser($uid, $path, $data, $out);
        if ($out['ms'] >= self::SLOW_MS) {
            @file_put_contents(storage_path('logs/sim/slow.log'), sprintf("[%s] ms=%d uid=%d %s http=%d\n", now()->format('Y-m-d H:i:s.v'), $out['ms'], $uid, $path, $out['status']), FILE_APPEND | LOCK_EX);
        }
        return $out;
    }

    private function tokensFile(): string
    {
        return storage_path('sim/tokens.json');
    }

    private function loadTokens(): void
    {
        $f = $this->tokensFile();
        if (is_file($f)) {
            $t = json_decode((string) @file_get_contents($f), true);
            if (is_array($t)) {
                $this->tokens = array_map('strval', $t);
            }
        }
    }

    private function saveTokens(): void
    {
        @mkdir(dirname($this->tokensFile()), 0775, true);
        @file_put_contents($this->tokensFile(), json_encode($this->tokens), LOCK_EX);
    }

    private function logUser(int $uid, string $path, array $data, array $out): void
    {
        $d = storage_path('logs/sim');
        if (!is_dir($d)) { @mkdir($d, 0775, true); }
        $ms = (int) ($out['ms'] ?? 0);
        $line = sprintf(
            "[%s] uid=%d %s result=%s http=%d ms=%d%s\n    req=%s\n    res=%s\n",
            now()->format('Y-m-d H:i:s.v'), $uid, $path, $out['ok'] ? 'OK' : 'FAIL', $out['status'], $ms,
            $ms >= self::SLOW_MS ? ' SLOW' : '',
            json_encode($data, JSON_UNESCAPED_UNICODE), (string) ($out['body'] ?? '')
        );
        @file_put_contents($d . "/user_{$uid}.log", $line, FILE_APPEND | LOCK_EX);
    }
}
