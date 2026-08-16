<?php

namespace App\Jobs;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Publishes ONE in-room data frame to UTD-Stream from a queue worker.
 *
 * send-data frames are fire-and-forget room signals (no caller reads the
 * engine's response), yet they ran synchronously inside the HTTP request:
 * whenever the engine degraded, every frame held an Octane worker for up to
 * the full send-data timeout (139 cURL timeouts in 24h on 2026-06-12, 6-10s
 * each). Queuing caps the request-path cost at a Redis enqueue; transport
 * failures stay logged + circuit-broken inside UtdStreamTrait::streamRequest.
 */
class PushStreamDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** $destinationIdentities mirrors UtdStreamTrait::sendToStream — a single
     *  identity (int|string, legacy sendToStream_3/_4 callers) or a list. */
    public function __construct(
        private string $roomName,
        private string $payload,
        private array|string|int|null $destinationIdentities = null,
    ) {
    }

    public function handle(): void
    {
        try {
            Common::streamSendData($this->roomName, $this->payload, $this->destinationIdentities);
        } catch (\Throwable $e) {
            Log::error('PushStreamDataJob.send_failed', [
                'room'  => $this->roomName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
