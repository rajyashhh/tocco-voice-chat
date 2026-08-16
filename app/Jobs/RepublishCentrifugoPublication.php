<?php

namespace App\Jobs;

use App\Broadcasting\CentrifugoBroadcaster;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Re-publishes a failed personal-channel (`user:#{id}`) publication to
 * Centrifugo with backoff.
 *
 * During the 2026-06-12 01:00 UTC node outage (caddy OOM on jo-realtime-1)
 * 256 publishes were dropped silently in one hour — 69 of them on user:#
 * channels (personal notifications/messages). Chat heals via sync-on-open and
 * banners are ephemeral, but a personal-channel publication that never reached
 * the node is lost forever (channel history can't replay what was never
 * published). So the broadcaster requeues exactly those here; transport errors
 * (ConnectionException out of publishNow) ride the queue's retry/backoff.
 */
class RepublishCentrifugoPublication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private array $channels,
        private array $data,
    ) {
    }

    /** Spread over the typical node-restart window (OOM kill -> back up). */
    public function backoff(): array
    {
        return [15, 60];
    }

    public function handle(BroadcastManager $manager): void
    {
        $broadcaster = $manager->driver('centrifugo');

        if (! $broadcaster instanceof CentrifugoBroadcaster) {
            return;
        }

        $broadcaster->publishNow($this->channels, $this->data);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('RepublishCentrifugoPublication.dropped', [
            'channels' => $this->channels,
            'event'    => $this->data['event'] ?? null,
            'error'    => $e->getMessage(),
        ]);
    }
}
