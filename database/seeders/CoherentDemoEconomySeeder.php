<?php

namespace Database\Seeders;

use App\Classes\Gifts\SendGiftService;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Models\Charge;
use App\Models\Gift;
use App\Models\GiftLog;
use App\Models\MonthlyDiamondReceive;
use App\Models\Room;
use App\Models\Setting;
use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\FixedTarget\Services\FixedTargetService;

/**
 * Coherent, end-to-end demo economy for the "أرباح المستخدمين" / salary pages.
 *
 * WHY THIS EXISTS
 * ---------------
 * DemoEarningsScenariosSeeder wrote user_sallaries / agency_sallaries rows DIRECTLY
 * (hand-picked gross figures) without ever touching the real money path. The panel
 * then showed "target = $X" while "charges = $0" — numbers with no source.
 *
 * This seeder instead DRIVES THE REAL RECORDED PATH so every target is traceable:
 *
 *   1) recorded admin charge  → charges + user_coin_logs + users.di   (chargers get coins)
 *   2) recorded gift          → gift_logs (agency_id snapshot, receiver_obtain)
 *                               + users.di down (sender) + monthly_diamond_receives (host)
 *   3) real salary engine     → FixedTargetService::calculateTarget() derives user_sallaries
 *                               from the host's actual monthly_diamond_receives + targets tiers
 *
 * Deliberately NOT registered in DatabaseSeeder. Run by hand on a demo database:
 *
 *   php artisan db:seed --class=CoherentDemoEconomySeeder --force
 *
 * ── SAFETY MODEL (post adversarial review) ──────────────────────────────────────
 * This seeder NEVER deletes or overwrites a salary/agency row by value fingerprint.
 * For the demo hosts it targets, the live engine's updateOrCreate REPLACES their
 * existing user_sallaries row (whatever wrote it) in place with a gift-derived value
 * — so incoherent demo rows for THOSE hosts self-heal, and no real row anywhere is
 * matched by a fragile value pattern. Cleaning up DemoEarningsScenariosSeeder's rows
 * for OTHER hosts / past months is intentionally OUT OF SCOPE here and left to a
 * separate, guarded command.
 *
 * It writes agency_sallaries for NOTHING: aggregating that table would overwrite a
 * real agency's live row (a demo host lives in a real agency). The per-host salary
 * page is fully coherent; a coherent agency-grid demo requires dedicated demo
 * agencies (separate task). See DISCLOSURES in the delivery notes.
 *
 * Idempotent: chargers are dedicated demo accounts whose spend-counters are reset at
 * start; the seeder deletes only ITS OWN prior artifacts (demo-charger gift_logs /
 * charges / coin logs for the month) and re-derives monthly_diamond_receives from the
 * gift_logs SUM, so a re-run converges instead of doubling.
 *
 * Leaderboard (Redis) is NOT polluted: gifts are written via getGiftLogData()+GiftLog
 * (the exact recorded row shape) WITHOUT SendGiftService::recordGiftRankings().
 */
class CoherentDemoEconomySeeder extends Seeder
{
    /** Demo hosts whose earnings the panel shows (owner-confirmed). */
    private const DEMO_HOST_IDS = [27, 31];

    /** Stable demo charger accounts, keyed to a demo host. Pure spenders (no agency). */
    private const CHARGERS = [
        27 => ['email' => 'demo.charger.1@meow.local', 'name' => 'Demo Charger 1'],
        31 => ['email' => 'demo.charger.2@meow.local', 'name' => 'Demo Charger 2'],
    ];

    /** Marker on the charge coin-log reason so re-runs can find & purge them. */
    private const MARKER = 'coherent-demo-economy';

    /** Fallback host monthly diamonds when no targets tiers are configured. */
    private const FALLBACK_DIAMONDS = 500000;

    public function run(): void
    {
        // ── (4) mandatory environment guard: never run on production by accident ──
        // the demo box is the intended target; if its APP_ENV is production, opt in
        // deliberately with ALLOW_DEMO_ECONOMY_SEED=true. Production stays protected.
        if (!app()->environment(['local', 'demo', 'staging']) && !env('ALLOW_DEMO_ECONOMY_SEED')) {
            $this->command?->error(
                'CoherentDemoEconomySeeder refused: environment is "' . app()->environment() .
                '". Run only on local/demo/staging, or set ALLOW_DEMO_ECONOMY_SEED=true to force on a demo box.'
            );
            return;
        }

        $now   = Carbon::now();
        $month = (int) $now->month;
        $year  = (int) $now->year;

        $hosts = User::query()
            ->whereIn('id', self::DEMO_HOST_IDS)
            ->get()
            ->keyBy('id');

        foreach (self::DEMO_HOST_IDS as $hostId) {
            $host = $hosts->get($hostId);
            if (!$host) {
                $this->command?->warn("CoherentDemoEconomySeeder: demo host {$hostId} not found — skipped.");
                continue;
            }
            if ((int) ($host->agency_id ?? 0) === 0 || (int) $host->type_user === 0) {
                $this->command?->warn(
                    "CoherentDemoEconomySeeder: host {$hostId} is not an agency host (agency_id/type_user) — the salary engine ignores it. Skipped."
                );
                $hosts->forget($hostId);
            }
        }

        if ($hosts->isEmpty()) {
            $this->command?->warn('CoherentDemoEconomySeeder: no qualifying demo hosts — nothing seeded.');
            return;
        }

        // Purge this seeder's own prior artifacts (idempotency) — no fingerprint deletes.
        $this->cleanupOwnPriorArtifacts($month, $year);

        $userCoins = (int) (Setting::where('key', 'user_coins')->value('value') ?: 1);
        $gift      = $this->pickDemoGift();
        if (!$gift) {
            $this->command?->warn('CoherentDemoEconomySeeder: no usable gift (price>0, non-lucky) found — cannot drive the gift path. Aborted.');
            return;
        }

        // charger_id on Charge is a NOT-NULL admin id — resolve dynamically, don't assume 1.
        $adminChargerId = (int) (DB::table('admin_users')->orderBy('id')->value('id') ?? 0);
        if ($adminChargerId === 0) {
            $this->command?->warn('CoherentDemoEconomySeeder: no admin_users row to attribute the charge to. Aborted.');
            return;
        }

        $tiers = Target::query()->orderBy('diamonds')->get(['id', 'diamonds', 'usd']);
        if ($tiers->isEmpty()) {
            $this->command?->warn('CoherentDemoEconomySeeder: no target tiers configured — hosts will achieve $0 salary. Seeding charges+gifts only.');
        }

        $sendGiftService  = new SendGiftService();
        $updateUserOnGift = new UpdateUserWhenSendGift();

        $hostIndex = 0;
        foreach ($hosts as $host) {
            $charger = $this->resolveCharger($host->id);

            $targetDiamonds = $this->targetDiamondsForHost($tiers, $hostIndex);
            $room           = $this->resolveHostRoom($host);

            $giftUnitPrice = (int) $gift->price;
            $number        = max(1, (int) ceil($targetDiamonds / max(1, $giftUnitPrice)));
            $giftTotal     = $giftUnitPrice * $number;

            $dollarsToCharge = (int) ceil($giftTotal / max(1, $userCoins)) + 5;

            DB::transaction(function () use (
                $charger, $host, $room, $gift, $number, $giftTotal,
                $dollarsToCharge, $userCoins, $adminChargerId,
                $sendGiftService, $updateUserOnGift, $month, $year
            ) {
                // ── (1) recorded admin charge: coin log + di credit + charges row ──
                // Mirrors App\Admin\Actions\UsersChargeAction::handleUserCharge faithfully
                // (there is no request-free service to call; the Action is Encore/auth-bound).
                $coins = $dollarsToCharge * $userCoins;

                $balanceBefore = Common::getCurrentBalance($charger->id);
                UserCoinLogHelper::logByType(
                    $charger->id,
                    $coins,
                    $balanceBefore,
                    UserCoinLogType::ADMIN_CHARGES,
                    self::MARKER,
                );

                $charger->di += $coins;
                $charger->save();

                Charge::create([
                    'charger_id'     => $adminChargerId,
                    'charger_type'   => 'dash',
                    'user_id'        => $charger->id,
                    'user_type'      => 'user',
                    'amount'         => $coins,
                    'amount_type'    => 2, // 2 = coins
                    'usd'            => $dollarsToCharge,
                    'balance_before' => $balanceBefore,
                ]);

                // ── (2) recorded gift: di down (sender) + gift_logs (agency snapshot) ──
                // Faithful live sequence from App\Tik\Services\GiftLogService::sendGift:
                //   coin log (GIFT) → UpdateUserWhenSendGift::send (di down, di>=total guard)
                //   → gift_logs row.
                $giftBalanceBefore = $charger->di;
                UserCoinLogHelper::logByType(
                    $charger->id,
                    -abs($giftTotal),
                    $giftBalanceBefore,
                    UserCoinLogType::GIFT,
                    $gift->name,
                );

                $updateUserOnGift->send($giftTotal, $charger);

                // Write the EXACT recorded gift_logs row shape, but WITHOUT the Redis
                // ranking push (SendGiftService::sendGift would call recordGiftRankings
                // and permanently inflate the live leaderboards). getGiftLogData is the
                // same builder the live path uses; we only skip the best-effort Redis side.
                $giftLogRow = $sendGiftService->getGiftLogData(
                    $gift, $room, $number, $giftTotal, $charger, $host, 0
                );
                GiftLog::query()->create($giftLogRow);

                // ── monthly_diamond_receives = SUM(gift_logs.giftPrice) for this host/month ──
                // The system invariant (see FixMonthlyDiamondsController / DiamondController):
                // authoritative & idempotent — reflects real + demo gifts, never doubles.
                // We do NOT touch the host's lifetime total_diamond_received (would drift on
                // re-run and it is not read by the salary engine, which reads this table).
                $monthlyDiamonds = (int) DB::table('gift_logs')
                    ->where('receiver_id', $host->id)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->sum('giftPrice');

                MonthlyDiamondReceive::updateOrCreate(
                    ['user_id' => $host->id, 'month' => $month, 'year' => $year],
                    ['monthly_diamond_received' => $monthlyDiamonds],
                );
            });

            // ── (3) real salary engine derives/reconciles user_sallaries in place ──
            // updateOrCreate on (user_id,month,year,agency,is_finished=0) overwrites any
            // pre-existing demo row for this host with a gift-derived value — no delete.
            (new FixedTargetService($host->fresh(), month: $month, year: $year))
                ->calculateTarget($month, $year);

            $hostIndex++;
        }

        $this->command?->info(
            'CoherentDemoEconomySeeder: seeded coherent economy for hosts [' .
            $hosts->pluck('id')->implode(', ') . "] for {$month}/{$year}. " .
            'agency_sallaries intentionally not written (see disclosures).'
        );
    }

    /**
     * Purge this seeder's own prior artifacts for the current month and reset the
     * dedicated demo chargers' spend counters, so a re-run converges instead of
     * accumulating: demo-charger gift_logs, demo-charger charges, demo-charger coin
     * logs; then di / monthly_diamond_send / total_diamond_send back to 0.
     *
     * Only dedicated demo charger accounts (our own emails) are ever touched here.
     */
    private function cleanupOwnPriorArtifacts(int $month, int $year): void
    {
        $chargerIds = User::query()
            ->whereIn('email', collect(self::CHARGERS)->pluck('email')->all())
            ->pluck('id')
            ->all();

        if (empty($chargerIds)) {
            return;
        }

        DB::transaction(function () use ($chargerIds, $month, $year) {
            DB::table('gift_logs')
                ->whereIn('sender_id', $chargerIds)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->delete();

            Charge::query()
                ->whereIn('user_id', $chargerIds)
                ->where('charger_type', 'dash')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->delete();

            DB::table('user_coin_logs')
                ->whereIn('user_id', $chargerIds)
                ->whereIn('type', [UserCoinLogType::ADMIN_CHARGES->value, UserCoinLogType::GIFT->value])
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->delete();

            // Re-derive (reset) the demo chargers' cosmetic counters so di/spend are
            // exactly this run's charge − this run's gift, not an ever-growing total.
            User::whereIn('id', $chargerIds)->update([
                'di'                   => 0,
                'monthly_diamond_send' => 0,
                'total_diamond_send'   => 0,
            ]);
        });
    }

    private function resolveCharger(int $hostId): User
    {
        $spec = self::CHARGERS[$hostId] ?? [
            'email' => "demo.charger.host{$hostId}@meow.local",
            'name'  => "Demo Charger {$hostId}",
        ];

        return User::firstOrCreate(
            ['email' => $spec['email']],
            [
                'name'      => $spec['name'],
                'password'  => bcrypt(str()->random(40)),
                'agency_id' => 0,
                'is_host'   => 0,
                'di'        => 0,
            ]
        );
    }

    private function resolveHostRoom(User $host): Room
    {
        $room = Room::where('uid', $host->id)->first();
        if ($room) {
            return $room;
        }

        return Room::create([
            'numid'     => (string) ($host->uuid ?: $host->id),
            'uid'       => $host->id,
            'room_name' => 'Demo Room ' . $host->id,
            'type'      => 'audio',
        ]);
    }

    private function pickDemoGift(): ?Gift
    {
        return Gift::query()
            ->where('price', '>', 0)
            ->where('type', '!=', 6) // never a lucky gift (GiftLogService blocks type===6 on the normal path)
            ->orderBy('enable')      // enable: 1=enabled, 2=disabled → ascending prefers an enabled gift
            ->orderBy('price')
            ->first();
    }

    /**
     * Pick a real tier the host will clear. Host index 0 aims one tier higher than 1
     * so the two demo hosts show distinct, real figures. Falls back to a fixed amount
     * when no tiers exist.
     */
    private function targetDiamondsForHost($tiers, int $hostIndex): int
    {
        if ($tiers->isEmpty()) {
            return self::FALLBACK_DIAMONDS;
        }

        $count = $tiers->count();
        $pick  = $hostIndex === 0
            ? (int) min($count - 1, (int) floor($count / 2) + 1)
            : (int) min($count - 1, (int) floor($count / 2));

        $diamonds = (int) $tiers->values()->get($pick)->diamonds;

        return $diamonds > 0 ? $diamonds : self::FALLBACK_DIAMONDS;
    }
}
