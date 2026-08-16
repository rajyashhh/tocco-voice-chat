<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * sim:activity — keep the test accounts (ids 3..102) realistically active inside
 * the app on the MEOW TEST server: move room-to-room, go up/down on mic, send
 * normal gifts + lucky gifts, and post room messages. ONE orchestrator that keeps
 * per-user state (which room/seat) so leave/mic-down target the right place, with
 * human-like pacing. Drives the REAL API with a per-user Sanctum token (same proven
 * pattern as RunStressTestJob) so it exercises the production path exactly.
 *
 * TEST-SERVER ONLY. Gated by the settings.json flag `sim_activity_enabled` (set it
 * to 0 to pause without killing the process). Run continuously under supervisor.
 */
class SimulateActivity extends Command
{
    protected $signature = 'sim:activity {--rounds=0 : 0 = run forever} {--subset=30 : users acting per round} {--sleep=5 : seconds between rounds} {--verbose-errors : print first error body per action}';

    protected $description = 'Drive test users (3..102) through realistic in-app activity (Meow test only).';

    private string $base = 'http://127.0.0.1:8099/api';
    private array $tokens = [];   // userId => plainTextToken
    private array $state = [];    // userId => ['room'=>id,'owner'=>uid,'seat'=>int|null]
    private array $rooms = [];    // [ ['id'=>, 'owner'=>], ... ]
    private array $normalGifts = [];
    private array $luckyGifts = [];
    private array $tally = [];
    private array $errSample = [];
    private array $chat = ['أهلاً بالكل 👋','نورتوا الغرفة','يلا نشّطوا معانا','الجو حلو هنا 🔥','مساء الخير','تسلموا يا جماعة','منوّر يا نجم','هاي 😄','احلى غرفة','تحية للكل ❤️'];

    public function handle()
    {
        $verbose = (bool) $this->option('verbose-errors');
        $maxRounds = (int) $this->option('rounds');
        $subset = max(1, (int) $this->option('subset'));
        $sleep = max(1, (int) $this->option('sleep'));

        $userIds = DB::table('users')->whereBetween('id', [3, 102])->where('di', '>', 0)->pluck('id')->all();
        $this->rooms = DB::table('rooms')->where('type', 'audio')->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'owner' => (int) $r->uid])->values()->all();
        $this->normalGifts = DB::table('gifts')->where('type', '!=', 6)->where('enable', 1)
            ->where('vip_level', 0)->where('is_play', 0)
            ->orderBy('price')->limit(40)->pluck('id')->all();
        $this->luckyGifts = DB::table('gifts')->where('type', 6)->where('enable', 1)->pluck('id')->all();

        if (empty($userIds) || empty($this->rooms)) {
            $this->error('Missing test users or audio rooms.');
            return self::FAILURE;
        }
        $this->info('users=' . count($userIds) . ' rooms=' . count($this->rooms)
            . ' normalGifts=' . count($this->normalGifts) . ' luckyGifts=' . count($this->luckyGifts));

        // Mint one token per user up front.
        foreach ($userIds as $uid) {
            $u = \App\Models\User::find($uid);
            if ($u) {
                $this->tokens[$uid] = $u->createToken('sim')->plainTextToken;
            }
        }

        $round = 0;
        while ($maxRounds === 0 || $round < $maxRounds) {
            $round++;
            if ($maxRounds === 0 && ((int) (settings()->get('sim_activity_enabled') ?? 1)) !== 1) {
                sleep($sleep);
                continue;
            }

            $actors = collect($userIds)->shuffle()->take($subset);
            foreach ($actors as $uid) {
                $this->act($uid, $verbose);
                usleep(random_int(40000, 180000)); // 40-180ms between users (human-ish)
            }

            $this->line('round ' . $round . ' | ' . collect($this->tally)->map(fn ($v, $k) => "$k=$v")->implode(' '));
            if ($maxRounds === 0 || $round < $maxRounds) {
                sleep(random_int(max(1, $sleep - 2), $sleep + 2));
            }
        }

        $this->info('DONE. tallies: ' . json_encode($this->tally, JSON_UNESCAPED_UNICODE));
        if ($verbose && $this->errSample) {
            $this->warn('error samples: ' . json_encode($this->errSample, JSON_UNESCAPED_UNICODE));
        }
        return self::SUCCESS;
    }

    private function act(int $uid, bool $verbose): void
    {
        try {
            $this->actInner($uid, $verbose);
        } catch (\Throwable $e) {
            // Nothing must escape unlogged — capture the full exception per user.
            $this->bump('exception');
            $this->logUser($uid, 'EXCEPTION', [], [
                'ok' => false, 'status' => 0,
                'body' => $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
            ]);
        }
    }

    private function actInner(int $uid, bool $verbose): void
    {
        $st = $this->state[$uid] ?? null;

        if (!$st) {
            $this->enterRoom($uid, $verbose);
            return;
        }

        // weighted action menu for a user already in a room. chat (yellow banner)
        // is intentionally RARE here + globally spaced (see chat()) so banners stay
        // readable — a banner is meant to be seen, not drowned by many at once.
        $menu = ['gift', 'gift', 'gift', 'lucky', 'lucky', 'lucky', 'idle', 'idle', 'chat'];
        $menu[] = ($st['seat'] === null) ? 'mic_up' : 'mic_down';
        $menu[] = 'leave'; // small chance
        $pick = $menu[array_rand($menu)];

        match ($pick) {
            'gift'     => $this->sendGift($uid, $verbose),
            'lucky'    => $this->sendLucky($uid, $verbose),
            'chat'     => $this->chat($uid, $verbose),
            'mic_up'   => $this->micUp($uid, $verbose),
            'mic_down' => $this->micDown($uid, $verbose),
            'leave'    => $this->leave($uid, $verbose),
            default    => $this->bump('idle'),
        };
    }

    private function post(int $uid, string $path, array $data): array
    {
        $t0 = microtime(true);
        try {
            $res = Http::withToken($this->tokens[$uid])->acceptJson()->timeout(15)
                ->asForm()->post($this->base . $path, $data);
            $out = ['ok' => $res->successful(), 'status' => $res->status(), 'body' => $res->body()];
        } catch (\Throwable $e) {
            $out = ['ok' => false, 'status' => 0, 'body' => 'EXCEPTION: ' . $e->getMessage()];
        }
        $out['ms'] = (int) round((microtime(true) - $t0) * 1000);
        // Full A-Z capture: every request + full response + latency to the user log.
        $this->logUser($uid, $path, $data, $out);
        // Latency watch: slow requests (>= SLOW_MS) also go to a central log so we
        // can monitor moment-by-moment which endpoints lag and need lightening.
        if ($out['ms'] >= self::SLOW_MS) {
            $this->bump('slow_req');
            @file_put_contents(
                storage_path('logs/sim/slow.log'),
                sprintf("[%s] ms=%d uid=%d %s http=%d\n", now()->format('Y-m-d H:i:s.v'), $out['ms'], $uid, $path, $out['status']),
                FILE_APPEND | LOCK_EX
            );
        }
        return $out;
    }

    private const SLOW_MS = 1000; // requests at/over this (ms) are flagged slow

    private function record(string $action, array $r, bool $verbose, int $uid = 0): bool
    {
        $this->bump($r['ok'] ? $action : $action . '_fail');
        if (!$r['ok'] && $verbose && !isset($this->errSample[$action])) {
            $this->errSample[$action] = ['status' => $r['status'], 'body' => mb_substr($r['body'], 0, 240)];
        }
        return $r['ok'];
    }

    /**
     * Per-user COMPLETE activity log: storage/logs/sim/user_{id}.log — for EVERY
     * action it records the full request (endpoint + params) AND the full server
     * response (status + entire body), success or fail, plus any exception. Nothing
     * is truncated or dropped, so no problem can occur without being captured.
     */
    private function logUser(int $uid, string $path, array $data, array $out): void
    {
        $dir = storage_path('logs/sim');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $room = $this->state[$uid]['room'] ?? '-';
        $ms = (int) ($out['ms'] ?? 0);
        $line = sprintf(
            "[%s] uid=%d %s result=%s http=%d ms=%d%s room=%s\n    req=%s\n    res=%s\n",
            now()->format('Y-m-d H:i:s.v'),
            $uid,
            $path,
            $out['ok'] ? 'OK' : 'FAIL',
            $out['status'],
            $ms,
            $ms >= self::SLOW_MS ? ' SLOW' : '',
            $room,
            json_encode($data, JSON_UNESCAPED_UNICODE),
            (string) ($out['body'] ?? '')
        );
        @file_put_contents($dir . "/user_{$uid}.log", $line, FILE_APPEND | LOCK_EX);
    }

    private function bump(string $k): void
    {
        $this->tally[$k] = ($this->tally[$k] ?? 0) + 1;
    }

    private function enterRoom(int $uid, bool $v): void
    {
        $room = $this->rooms[array_rand($this->rooms)];
        $r = $this->post($uid, '/rooms/enter_room', ['room_id' => $room['id'], 'owner_id' => $room['owner']]);
        if ($this->record('enter', $r, $v, $uid)) {
            $this->state[$uid] = ['room' => $room['id'], 'owner' => $room['owner'], 'seat' => null];
        }
    }

    private function leave(int $uid, bool $v): void
    {
        $st = $this->state[$uid];
        $r = $this->post($uid, '/rooms/quit_room', ['room_id' => $st['room'], 'owner_id' => $st['owner']]);
        $this->record('leave', $r, $v, $uid);
        unset($this->state[$uid]);
    }

    private function micUp(int $uid, bool $v): void
    {
        $st = $this->state[$uid];
        $pos = random_int(1, 8);
        $r = $this->post($uid, '/rooms/up-microphone', ['user_id' => $uid, 'room_id' => $st['room'], 'owner_id' => $st['owner'], 'position' => $pos]);
        if ($this->record('mic_up', $r, $v, $uid)) {
            $this->state[$uid]['seat'] = $pos;
        }
    }

    private function micDown(int $uid, bool $v): void
    {
        $st = $this->state[$uid];
        $r = $this->post($uid, '/rooms/leave-microphone', ['user_id' => $uid, 'room_id' => $st['room'], 'owner_id' => $st['owner'], 'position' => $st['seat']]);
        if ($this->record('mic_down', $r, $v, $uid)) {
            $this->state[$uid]['seat'] = null;
        }
    }

    private function sendGift(int $uid, bool $v): void
    {
        $st = $this->state[$uid];
        if (empty($this->normalGifts) || $st['owner'] == $uid) { $this->bump('gift_skip'); return; }
        $r = $this->post($uid, '/gifts/send', [
            'id' => $this->normalGifts[array_rand($this->normalGifts)],
            'toUid' => (string) $st['owner'], 'num' => 1,
            'owner_id' => $st['owner'], 'room_id' => $st['room'], 'type' => 1,
        ]);
        $this->record('gift', $r, $v, $uid);
    }

    private function sendLucky(int $uid, bool $v): void
    {
        $st = $this->state[$uid];
        if (empty($this->luckyGifts) || $st['owner'] == $uid) { $this->bump('lucky_skip'); return; }
        $r = $this->post($uid, '/gifts/v2/send-lucky-gift-combo', [
            'id' => $this->luckyGifts[array_rand($this->luckyGifts)],
            'toUid' => (string) $st['owner'], 'num' => 1, 'count' => 1,
            'owner_id' => $st['owner'], 'room_id' => $st['room'], 'nonce' => (string) \Illuminate\Support\Str::uuid(),
        ]);
        $this->record('lucky', $r, $v, $uid);
    }

    private float $lastBanner = 0.0;

    private function chat(int $uid, bool $v): void
    {
        $st = $this->state[$uid];
        // Global spacing: at most one banner every ~10s so each is readable
        // (a banner exists to be clicked; many at once defeat the purpose).
        $now = microtime(true);
        if ($now - $this->lastBanner < 10.0) { $this->bump('chat_skip'); return; }
        $this->lastBanner = $now;
        $r = $this->post($uid, '/rooms/yellow-banner', [
            'room_id' => $st['room'], 'owner_id' => $st['owner'],
            'message' => $this->chat[array_rand($this->chat)],
        ]);
        $this->record('chat', $r, $v, $uid);
    }
}
