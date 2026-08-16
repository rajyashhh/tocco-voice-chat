<?php

namespace App\Console\Commands;

use App\Models\FairLuckWallet;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Reconciler / self-healer for the O(1) daily lucky-gift report counters.
 *
 * The admin lucky-gift-reports "today" cards are served from a per-day Redis hash
 * (lucky:day:{date}) the gift job increments per committed batch, plus a per-day
 * SET of distinct senders. A best-effort bump failure (Redis blip) can leave the
 * day undercounted. This command rebuilds the hash + players SET from the SAME
 * additive SQL over fair_luck_transactions and atomically replaces them — making
 * the counters exactly equal to the report's GROUP-less aggregate. Idempotent and
 * safe to re-run; scheduled for "today" every 10 minutes as a drift guard.
 *
 *   --date=YYYY-MM-DD : rebuild this single day (or 'today'; default today).
 *   --days=N          : also rebuild the last N days ending today (oldest first).
 *
 * Past days are never READ from Redis by the page (it routes them to the pre-agg
 * table), so they only need rebuilding for an explicit audit — hence --days is opt-in.
 */
class LuckyRebuildDayCounters extends Command
{
    protected $signature = 'lucky:rebuild-day-counters
                            {--date= : Rebuild this single day (YYYY-MM-DD or "today"); defaults to today}
                            {--days= : Rebuild the last N days ending today}';

    protected $description = 'Rebuild the O(1) daily lucky-gift report counters from fair_luck_transactions (drift guard)';

    private const TTL = 172800; // 48h, matches the live counter expiry

    public function handle(): int
    {
        if (!Schema::hasTable('fair_luck_transactions')) {
            $this->error('fair_luck_transactions missing');
            return self::FAILURE;
        }

        foreach ($this->resolveDates() as $date) {
            try {
                $this->rebuildDay($date);
                $this->info("rebuilt counters for {$date->toDateString()}");
            } catch (\Throwable $e) {
                Log::error('lucky:rebuild-day-counters failed', [
                    'date' => $date->toDateString(),
                    'error' => $e->getMessage(),
                ]);
                $this->error("{$date->toDateString()}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return Carbon[]
     */
    private function resolveDates(): array
    {
        $days = $this->option('days');
        if ($days !== null && ctype_digit((string) $days) && (int) $days > 0) {
            $n = (int) $days;
            $dates = [];
            for ($i = $n; $i >= 0; $i--) {
                $dates[] = Carbon::today()->subDays($i);
            }
            return $dates;
        }

        $date = $this->option('date');
        if (is_string($date) && $date !== '' && strtolower($date) !== 'today') {
            try {
                return [Carbon::createFromFormat('Y-m-d', $date)->startOfDay()];
            } catch (\Throwable $e) {
                $this->error("invalid --date '{$date}', falling back to today");
            }
        }

        return [Carbon::today()];
    }

    private function rebuildDay(Carbon $day): void
    {
        $dateStr = $day->toDateString();
        $from = $day->copy()->startOfDay();
        $to = $day->copy()->endOfDay();

        // Same additive SQL the on-read seed / page summary use.
        $row = DB::table('fair_luck_transactions')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw(
                'COALESCE(COUNT(*), 0) AS rounds,'
                . ' COALESCE(SUM(bet_amount), 0) AS total_bets,'
                . ' COALESCE(SUM(profit_amount), 0) AS senders_net,'
                . ' COALESCE(SUM(CASE WHEN is_winner = 1 THEN profit_amount + bet_amount ELSE 0 END), 0) AS total_payouts,'
                . ' COALESCE(SUM(receiver_fee), 0) AS receivers_total,'
                . ' COALESCE(SUM(app_fee), 0) AS app_total,'
                . ' COALESCE(SUM(is_winner = 1), 0) AS wins'
            )
            ->first();

        // Owner fee stored in bps-coins (= SUM(app_fee) × 10000, exact integer) so the
        // sub-coin owner cut on cheap gifts (10-coin bet at 1% → 0.10) is never floored
        // to 0. This MUST match the live counter (ProcessLuckyGiftPostJob) and the
        // on-read seed (LuckyGiftsReportController) field-for-field — this reconciler
        // does an atomic DEL+HSET every 10 min, so a stale 'app_total' here would
        // overwrite the bps field and silently revert the fraction fix on "today".
        $appTotalBps = (int) round(((float) ($row->app_total ?? 0)) * 10000);
        // Receiver fee stored in bps-coins (= SUM(receiver_fee) × 10000, exact integer)
        // so the sub-coin receiver cut on cheap gifts (50-coin bet at 1% → 0.50) is
        // never floored to 0. MUST match the live counter (ProcessLuckyGiftPostJob) and
        // the on-read seed (LuckyGiftsReportController) field-for-field — the atomic
        // DEL+HSET below would otherwise silently revert the fraction fix on "today".
        $receiversTotalBps = (int) round(((float) ($row->receivers_total ?? 0)) * 10000);

        $fields = [
            'rounds'              => (int) ($row->rounds ?? 0),
            'total_bets'          => (int) ($row->total_bets ?? 0),
            'senders_net'         => (int) ($row->senders_net ?? 0),
            'total_payouts'       => (int) ($row->total_payouts ?? 0),
            'receivers_total_bps' => $receiversTotalBps,
            'app_total_bps'       => $appTotalBps,
            'wins'                => (int) ($row->wins ?? 0),
        ];

        $redis = FairLuckWallet::vaultRedis();
        $hkey = "lucky:day:{$dateStr}";
        $pkey = "lucky:day:{$dateStr}:players";

        // Surface any drift before overwriting so a best-effort bump miss is
        // observable in Loki and confirmed auto-corrected here.
        $current = $redis->hgetall($hkey);
        if (!empty($current)) {
            foreach ($fields as $k => $v) {
                if ((int) ($current[$k] ?? 0) !== $v) {
                    Log::warning('lucky daily counter drift corrected', [
                        'date' => $dateStr,
                        'field' => $k,
                        'redis' => (int) ($current[$k] ?? 0),
                        'sql' => $v,
                    ]);
                }
            }
        }

        // Atomic replace: DEL + HSET + EXPIRE.
        $redis->del($hkey);
        $redis->hmset($hkey, $fields + ['seeded' => 1]);
        $redis->expire($hkey, self::TTL);

        // Rebuild the distinct-senders SET from SQL (SCARD = COUNT(DISTINCT user_id)).
        //
        // CRITICAL: page strictly inside the day window via the created_at index.
        // A GROUP BY user_id / ORDER BY user_id shape lets the optimizer prefer the
        // user_id-LEADING index (idx_flt_user_created_bet) and full-index-scan ALL
        // users on jo's 11.7M rows — the day range can't prune off a user_id lead,
        // so the job blew its 30s limit (MySQL 3024) every ~10 min. Instead we keyset
        // on (created_at, id) — the SAME idx_flt_created_at range read the page/seed
        // use — touching ONLY the day's rows (~86k) and dedupe user_id in PHP.
        // forceIndex(idx_flt_created_at) pins the day-range plan even when MySQL's
        // stale stats would otherwise misjudge the user_id index as cheaper.
        //
        // Result is identical to the old DISTINCT user_id over the day window: one SET
        // member per distinct sender that bet in [from, to]. PHP-side dedupe + Redis
        // SET semantics make the union of all chunks the exact distinct-sender set.
        $redis->del($pkey);
        $lastCreatedAt = null;
        $lastId = null;
        $seen = [];
        while (true) {
            $rows = DB::table('fair_luck_transactions')
                ->forceIndex('idx_flt_created_at')
                ->whereBetween('created_at', [$from, $to])
                ->when(
                    $lastCreatedAt !== null,
                    fn ($q) => $q->whereRaw(
                        '(created_at > ? OR (created_at = ? AND id > ?))',
                        [$lastCreatedAt, $lastCreatedAt, $lastId]
                    )
                )
                ->select('id', 'created_at', 'user_id')
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit(5000)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            $ids = [];
            foreach ($rows as $r) {
                $uid = (int) $r->user_id;
                if ($uid > 0 && !isset($seen[$uid])) {
                    $seen[$uid] = true;
                    $ids[] = $uid;
                }
            }
            if (!empty($ids)) {
                $redis->sadd($pkey, ...$ids);
            }

            $last = $rows->last();
            $lastCreatedAt = $last->created_at;
            $lastId = $last->id;

            if ($rows->count() < 5000) {
                break;
            }
        }
        $redis->expire($pkey, self::TTL);
    }
}
