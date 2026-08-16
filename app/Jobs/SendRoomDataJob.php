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
 * Pushes one or more in-room realtime frames to a SINGLE room over UTD-Stream
 * (the in-room data channel — see RoomDataTrait/UtdStreamTrait).
 *
 * Kept off the request hot path on purpose: UTD-Stream's send-data call can take
 * up to its HTTP timeout, and gift sending is the busiest endpoint in the app
 * (504-sensitive). The HTTP publish therefore runs on a queue worker, never in
 * the gift transaction. Each frame is a ready JSON string in the exact
 * `{messageContent:{message,...}}` envelope the client's RoomMessageProcessor
 * expects; we publish it through Common::sendToStream in the same
 * SendCustomCommand envelope the sendToStream shim builds — directly, because the
 * shim itself now queues (PushStreamDataJob) and this job already IS the queue
 * hop (re-dispatching would double every gift frame's queue cost for nothing).
 */
class SendRoomDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @param string[] $jsonFrames ready-encoded `{messageContent:{...}}` strings */
    public function __construct(
        private int|string $roomId,
        private int|string $fromUserId,
        private array $jsonFrames,
    ) {
    }

    public function handle(): void
    {
        foreach ($this->jsonFrames as $json) {
            if (!is_string($json) || $json === '') {
                continue;
            }

            try {
                Common::streamSendData((string) $this->roomId, json_encode([
                    'Action'         => 'SendCustomCommand',
                    'MessageContent' => $json,
                    'FromUserId'     => $this->fromUserId,
                ]));
            } catch (\Throwable $e) {
                Log::error('SendRoomDataJob.send_failed', [
                    'room_id' => $this->roomId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }
}
