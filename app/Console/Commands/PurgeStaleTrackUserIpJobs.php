<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Surgically drains stale/un-rehydratable TrackUserIpJob payloads from the Redis
 * queue (list + reserved zset + delayed zset for one queue).
 *
 * SCOPE: removes ONLY entries whose Laravel commandName === App\Jobs\TrackUserIpJob
 * AND whose serialized command body is unusable (fails to unserialize or comes back
 * as __PHP_Incomplete_Class). Every other job class (lucky_gift, increment-diamond,
 * heavy*, notifications, ...) is left untouched. NEVER flushes the whole queue.
 *
 * Complements the job hardening: the job stops NEW failures; this command drains
 * the EXISTING poison already sitting in Redis. Run with --dry-run first.
 */
class PurgeStaleTrackUserIpJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:purge-stale-track-ip {--queue=default : Queue name to scan} {--dry-run : Count matches without removing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Surgically remove stale/incomplete TrackUserIpJob payloads from the Redis queue (leaves all other jobs untouched)';

    private const TARGET_COMMAND = 'App\\Jobs\\TrackUserIpJob';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $queue = (string) $this->option('queue');
        $dryRun = (bool) $this->option('dry-run');

        // The redis queue connection ('default' per config/queue.php:120). The
        // phpredis client auto-applies the configured key prefix, matching the
        // keys Laravel's RedisQueue reads/writes.
        $redis = Redis::connection('default');

        $this->info(sprintf(
            'Scanning queue "%s" for stale TrackUserIpJob payloads%s...',
            $queue,
            $dryRun ? ' (dry-run)' : ''
        ));

        $scanned = 0;
        $matched = 0;
        $removed = 0;

        // 1) Pending list: queues:{queue}
        $listKey = "queues:{$queue}";
        foreach ((array) $redis->lrange($listKey, 0, -1) as $rawPayload) {
            $scanned++;
            if (!$this->isStaleTrackUserIp($rawPayload)) {
                continue;
            }
            $matched++;
            if (!$dryRun) {
                // LREM count=0 removes ALL occurrences equal to this exact payload.
                $removed += (int) $redis->lrem($listKey, 0, $rawPayload);
            }
        }

        // 2) Reserved zset: queues:{queue}:reserved  3) Delayed zset: queues:{queue}:delayed
        foreach ([":reserved", ":delayed"] as $suffix) {
            $zKey = "queues:{$queue}{$suffix}";
            foreach ((array) $redis->zrange($zKey, 0, -1) as $rawPayload) {
                $scanned++;
                if (!$this->isStaleTrackUserIp($rawPayload)) {
                    continue;
                }
                $matched++;
                if (!$dryRun) {
                    $removed += (int) $redis->zrem($zKey, $rawPayload);
                }
            }
        }

        $summary = [
            'queue' => $queue,
            'dry_run' => $dryRun,
            'scanned' => $scanned,
            'matched' => $matched,
            'removed' => $removed,
        ];

        $this->table(['scanned', 'matched', 'removed', 'dry_run'], [[
            $scanned,
            $matched,
            $removed,
            $dryRun ? 'yes' : 'no',
        ]]);

        if ($dryRun) {
            $this->info("Dry-run: {$matched} stale TrackUserIpJob payload(s) would be removed. Re-run without --dry-run to purge.");
        } else {
            $this->info("Removed {$removed} stale TrackUserIpJob payload(s).");
        }

        Log::info('queue:purge-stale-track-ip completed', $summary);

        return self::SUCCESS;
    }

    /**
     * True only when the raw Redis payload is a TrackUserIpJob whose serialized
     * command body is unusable (cannot unserialize, or returns an incomplete class).
     * Healthy TrackUserIpJob payloads are intentionally left in place.
     */
    private function isStaleTrackUserIp(string $rawPayload): bool
    {
        $decoded = json_decode($rawPayload, true);
        if (!is_array($decoded)) {
            return false;
        }

        $commandName = $decoded['data']['commandName'] ?? null;
        if ($commandName !== self::TARGET_COMMAND) {
            return false;
        }

        $command = $decoded['data']['command'] ?? null;
        if (!is_string($command)) {
            // Malformed TrackUserIpJob entry with no serialized body — treat as stale.
            return true;
        }

        // Guarded unserialize: a stale/incompatible body throws or yields an
        // __PHP_Incomplete_Class. Either condition marks it for removal.
        $object = @unserialize($command);
        if ($object === false) {
            return true;
        }
        if (!is_object($object)) {
            return true;
        }
        if ($object instanceof \__PHP_Incomplete_Class) {
            return true;
        }

        // Successfully rehydrated into a real TrackUserIpJob — leave it alone.
        return false;
    }
}
