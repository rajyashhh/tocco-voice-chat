<?php

namespace App\Jobs;

use App\Models\Ip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Persists the last known IP for a user asynchronously.
 *
 * PERFORMANCE FIX: IpMiddleware used to run a synchronous updateOrCreate on
 * every authenticated request, turning each read into a DB write on a saturated
 * connection pool. This job moves that write off the hot path (same pattern as
 * UpdateUserLastSeenJob). A Cache::add gate in the middleware guarantees this
 * job is dispatched at most once per (ip,uid) per time window, so the feature
 * semantics (record the user's last ip) are preserved without the per-request cost.
 *
 * The unique (ip,uid) index makes the updateOrCreate lookup a point query.
 *
 * DURABILITY: this is best-effort telemetry (last-known IP). A lost write is
 * harmless, so the job must NOT poison the queue. Every property carries a safe
 * default and is nullable, so a stale/partial payload deserialized by a worker
 * running newer code still rehydrates into a usable object instead of fataling on
 * an uninitialized typed property. tries=1 + failed() guarantee a bad message is
 * dead-lettered once, never retried into a storm.
 */
class TrackUserIpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Best-effort telemetry: a single attempt is correct. A poison payload must
     * never be retried (queue retry_after=300 would otherwise spin it).
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 30;

    protected ?string $ip = null;
    protected ?int $uid = null;
    protected int $userType = 0;

    public function __construct(?string $ip = null, ?int $uid = null, int $userType = 0)
    {
        $this->ip = $ip;
        $this->uid = $uid;
        $this->userType = $userType;
    }

    public function handle(): void
    {
        // Guard against stale/partial payloads (e.g. a post-deploy serialization
        // mismatch that rehydrated without usable values). Nothing to persist —
        // skip quietly instead of dead-lettering.
        if ($this->ip === null || $this->uid === null) {
            Log::warning('TrackUserIpJob: skipped stale/invalid payload', [
                'ip' => $this->ip,
                'uid' => $this->uid,
                'user_type' => $this->userType,
            ]);
            return;
        }

        try {
            Ip::query()->updateOrCreate(
                [
                    'ip' => $this->ip,
                    'uid' => $this->uid,
                ],
                [
                    'user_type' => $this->userType,
                ]
            );
        } catch (\Throwable $e) {
            // Best-effort telemetry: swallow transient DB hiccups so they don't
            // dead-letter or re-throw into a retry. Record once and move on.
            Log::warning('TrackUserIpJob: write failed, skipping', [
                'ip' => $this->ip,
                'uid' => $this->uid,
                'user_type' => $this->userType,
                'error' => $e->getMessage(),
            ]);
            return;
        }
    }

    /**
     * Handle a job failure.
     *
     * Catches the case where the framework cannot rehydrate the payload before
     * handle() is reached. With tries=1 this is logged exactly once, then gone —
     * never retried.
     */
    public function failed(\Throwable $exception): void
    {
        Log::warning('TrackUserIpJob: failed permanently', [
            'ip' => $this->ip,
            'uid' => $this->uid,
            'user_type' => $this->userType,
            'error' => $exception->getMessage(),
        ]);
    }
}
