<?php

namespace App\Console\Commands;

use App\Models\FairLuckWallet;
use App\Services\Gifts\LuckyMoneyJournal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * lucky:replay-fallback   (§7.1)
 *
 * Drains the best-effort fallback list LuckyMoneyJournal::REDIS_FALLBACK_LIST
 * (`lucky:refund_failures:fallback`) — items written ONLY when the journal table
 * itself was unreachable at compensation time, so they never reached the durable
 * lucky_refund_failures journal and were never credited.
 *
 * For every item:
 *   1. fingerprint = sha1(JSON)  — content-addressed identity of the item.
 *   2. INSERT IGNORE into lucky_refund_failures keyed by UNIQUE(source_fingerprint).
 *      A re-run (or a duplicate item in the list) collapses to the SAME row.
 *   3. S-CON-2: SELECT id,state WHERE source_fingerprint=?  — never trust
 *      insertGetId() under INSERT IGNORE (it returns 0 on a duplicate, which
 *      would call applyClaimed(0), apply nothing, never LREM, and loop forever).
 *        state=applied → already credited (this run or a previous one) → LREM only.
 *        state=pending → LuckyMoneyJournal::applyClaimed(actualId) (claim-first
 *                        pending→applied in one tx; exactly-once even against a
 *                        concurrent lucky:reconcile-intents) → then LREM.
 *      LREM is the LAST step in both branches: the item is removed from Redis
 *      ONLY after the credit is durably recorded/applied, so a crash mid-run
 *      re-processes it idempotently next run (the fingerprint already exists →
 *      no double credit).
 *
 * dry-run reports what WOULD happen and compares against the known expected
 * baseline (8 items / Σ=550 coins / user 3818) without touching Redis or the DB.
 *
 * The same drain logic is exposed as drainOnce() so the reconcile command can
 * run it as the permanent fallback drain (§5.2) — the list stays covered forever.
 */
class ReplayLuckyFallbackCredits extends Command
{
    protected $signature = 'lucky:replay-fallback
        {--dry-run : Inspect the fallback list and compare with the expected baseline without crediting anything}
        {--limit=10000 : Max fallback items to process this run}';

    protected $description = 'Replay lucky-gift compensations stranded on the Redis fallback list into the durable journal (idempotent, fingerprint-keyed).';

    /** Expected one-time backlog used to sanity-check the dry-run (§7.1). */
    public const EXPECTED_COUNT  = 8;
    public const EXPECTED_SUM    = 550;
    public const EXPECTED_USER   = 3818;

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        if ($this->option('dry-run')) {
            return $this->dryRun($limit);
        }

        $applied = self::drainOnce($limit);
        $this->info("lucky:replay-fallback applied {$applied} stranded compensation(s).");
        return self::SUCCESS;
    }

    /**
     * Inspect-only pass: read the list, fingerprint each item, report the credit
     * each WOULD trigger and how the totals line up with the expected backlog.
     * Does NOT pop, insert, credit, or otherwise mutate state.
     */
    private function dryRun(int $limit): int
    {
        $redis = FairLuckWallet::vaultRedis();
        $list = $redis->lrange(LuckyMoneyJournal::REDIS_FALLBACK_LIST, 0, $limit - 1);

        $count = 0;
        $sum = 0;
        $byUser = [];
        foreach ($list as $raw) {
            $decoded = json_decode($raw, true);
            $amount = is_array($decoded) ? (int) ($decoded['amount'] ?? 0) : 0;
            $userId = is_array($decoded) ? (int) ($decoded['user_id'] ?? 0) : 0;
            $fp = self::fingerprint($raw);
            $existing = DB::table('lucky_refund_failures')
                ->where('source_fingerprint', $fp)
                ->value('state');
            $this->line(sprintf(
                '  user=%d amount=%d fp=%s journal=%s',
                $userId, $amount, substr($fp, 0, 12), $existing ?? 'none'
            ));
            $count++;
            $sum += $amount;
            $byUser[$userId] = ($byUser[$userId] ?? 0) + $amount;
        }

        $this->info("DRY-RUN: {$count} item(s), Σ={$sum} coins.");
        $this->line('  by user: ' . json_encode($byUser));

        $matches = $count === self::EXPECTED_COUNT
            && $sum === self::EXPECTED_SUM
            && ($byUser[self::EXPECTED_USER] ?? 0) === self::EXPECTED_SUM;
        if ($matches) {
            $this->info(sprintf(
                'Matches expected backlog (%d items / Σ=%d / user %d).',
                self::EXPECTED_COUNT, self::EXPECTED_SUM, self::EXPECTED_USER
            ));
        } else {
            $this->warn(sprintf(
                'Does NOT match expected backlog (%d items / Σ=%d / user %d) — investigate before the real run.',
                self::EXPECTED_COUNT, self::EXPECTED_SUM, self::EXPECTED_USER
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Idempotent drain of the fallback list. Safe to call repeatedly and from the
     * reconcile schedule (§5.2). Returns the number of items credited THIS call
     * (already-applied items are pruned but not counted).
     */
    public static function drainOnce(int $limit = 10000): int
    {
        $redis = FairLuckWallet::vaultRedis();

        // Snapshot the head of the list ONCE. We process this immutable snapshot
        // and only trim the consumed prefix off Redis at the very end, so a crash
        // mid-run re-processes the SAME snapshot (same per-content ordinals →
        // same fingerprints → already-applied rows are skipped, exactly-once).
        $snapshot = $redis->lrange(LuckyMoneyJournal::REDIS_FALLBACK_LIST, 0, $limit - 1);
        if (empty($snapshot)) {
            return 0;
        }

        $applied = 0;
        $processed = 0;   // snapshot items fully handled (credited / already-applied / dropped)
        $ordinalSeen = []; // per-content occurrence counter within THIS snapshot

        foreach ($snapshot as $raw) {
            // Distinct list elements are distinct obligations even when their JSON
            // is byte-identical (legacy fallback items carry only 1-second `ts`,
            // so two same-second same-amount credits collide on content alone).
            // Identity = content hash + occurrence index within the snapshot.
            $hash = self::fingerprint($raw);
            $ordinal = $ordinalSeen[$hash] ?? 0;
            $ordinalSeen[$hash] = $ordinal + 1;
            $fingerprint = $hash . ':' . $ordinal;

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                Log::error('lucky:replay-fallback dropping unparseable fallback item', ['raw' => $raw]);
                $processed++;
                continue;
            }

            $userId = (int) ($decoded['user_id'] ?? 0);
            $amount = (int) ($decoded['amount'] ?? 0);
            $type = (string) ($decoded['type'] ?? LuckyMoneyJournal::TYPE_BET_REFUND);

            if ($userId <= 0 || $amount <= 0) {
                Log::error('lucky:replay-fallback dropping invalid fallback item (no user/amount)', ['item' => $decoded]);
                $processed++;
                continue;
            }

            // INSERT IGNORE: UNIQUE(source_fingerprint) makes a re-run a no-op for
            // an obligation already recorded (same content+ordinal → same key).
            DB::table('lucky_refund_failures')->insertOrIgnore([
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'gift_id' => $decoded['gift_id'] ?? null,
                'room_id' => $decoded['room_id'] ?? null,
                'reason' => isset($decoded['reason']) && $decoded['reason'] !== null
                    ? mb_substr((string) $decoded['reason'], 0, 500) : null,
                'state' => 'pending',
                'source_fingerprint' => $fingerprint,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // S-CON-2: read the REAL row id/state by fingerprint (insertGetId is
            // unreliable under INSERT IGNORE on a duplicate).
            $row = DB::table('lucky_refund_failures')
                ->where('source_fingerprint', $fingerprint)
                ->first(['id', 'state']);

            if ($row === null) {
                // DB blip: stop WITHOUT trimming the unprocessed tail. The prefix
                // handled so far is trimmed below; this item retries next run.
                Log::error('lucky:replay-fallback could not read journal row after insert - leaving item for retry', [
                    'fingerprint' => $fingerprint,
                ]);
                break;
            }

            if ($row->state === 'applied') {
                // Already credited (prior run / concurrent reconcile) → count as
                // processed (it will be trimmed) but not credited again.
                $processed++;
                continue;
            }

            // state === 'pending' (or any non-applied) → claim-first apply.
            if (LuckyMoneyJournal::applyClaimed((int) $row->id, $userId, $amount)) {
                $applied++;
                $processed++;
                Log::warning('lucky:replay-fallback credited stranded compensation', [
                    'row_id' => $row->id, 'user_id' => $userId, 'amount' => $amount, 'type' => $type,
                ]);
            } else {
                // Could not credit now (DB error / another worker). Stop before
                // this item; it stays on the list and retries next run.
                Log::warning('lucky:replay-fallback deferred item - credit not applied this run, kept on list', [
                    'row_id' => $row->id, 'user_id' => $userId, 'amount' => $amount,
                ]);
                break;
            }
        }

        // Remove ONLY the fully-handled prefix from the head. LTRIM keeps indices
        // [$processed .. -1], i.e. any unprocessed tail (and items pushed
        // concurrently during the drain) survive for the next run.
        if ($processed > 0) {
            $redis->ltrim(LuckyMoneyJournal::REDIS_FALLBACK_LIST, $processed, -1);
        }

        return $applied;
    }

    /**
     * Content hash of a fallback list entry (sha1 over the EXACT raw JSON). It is
     * combined with a per-snapshot occurrence ordinal (`hash:n`) to form the
     * UNIQUE source_fingerprint, so byte-identical-but-distinct obligations each
     * get their own journal row instead of collapsing into one (underpayment).
     */
    public static function fingerprint(string $rawJson): string
    {
        return sha1($rawJson);
    }
}
