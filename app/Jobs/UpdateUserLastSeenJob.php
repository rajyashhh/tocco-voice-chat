<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ChatMessageBatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Http\Repositories\ChatRepository;

class UpdateUserLastSeenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId
    ) {
        // Run on default queue to not block critical operations
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     *
     * This job handles the heavy operations that were previously blocking
     * the request cycle in UpdateLastSeen middleware:
     * 1. Get user's chat rooms
     * 2. Mark messages as received in batch
     * 3. Update user's last_seen_at timestamp
     */
    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            $user = User::find($this->userId);

            if (!$user) {
                Log::warning("UpdateUserLastSeenJob: User not found", ['user_id' => $this->userId]);
                return;
            }

            // Get user's chat rooms
            $chatRoomsStartTime = microtime(true);
            $chatsId = (new ChatRepository())->getUserChatRooms($user->id);
            $chatRoomsTime = microtime(true) - $chatRoomsStartTime;

            $chatRoomCount = count($chatsId);

            // Log if user has too many chat rooms or if it's taking too long
            if ($chatRoomCount > 100 || $chatRoomsTime > 5) {
                Log::warning("UpdateUserLastSeenJob: Slow chat room retrieval", [
                    'user_id' => $this->userId,
                    'chat_room_count' => $chatRoomCount,
                    'retrieval_time' => round($chatRoomsTime, 2) . 's',
                ]);
            }

            // Mark messages as received in batch (if user has chat rooms)
            if (!empty($chatsId)) {
                $batchStartTime = microtime(true);
                app(ChatMessageBatchService::class)->markMessagesAsReceivedInBatch(
                    $chatsId,
                    $user->id
                );
                $batchTime = microtime(true) - $batchStartTime;

                // Log if batch processing is slow
                if ($batchTime > 10) {
                    Log::warning("UpdateUserLastSeenJob: Slow batch processing", [
                        'user_id' => $this->userId,
                        'chat_room_count' => $chatRoomCount,
                        'batch_time' => round($batchTime, 2) . 's',
                    ]);
                }
            }

            // Update last_seen_at and online status
            $user->update([
                'last_seen_at' => now(),
                'online' => true,
            ]);

            $totalTime = microtime(true) - $startTime;

            // Log slow operations (especially for problematic users like 9451)
            if ($totalTime > 20 || $this->userId == 9451) {
                Log::info("UpdateUserLastSeenJob: Performance metrics", [
                    'user_id' => $this->userId,
                    'total_time' => round($totalTime, 2) . 's',
                    'chat_room_count' => $chatRoomCount,
                    'chat_rooms_time' => round($chatRoomsTime, 2) . 's',
                    'batch_time' => isset($batchTime) ? round($batchTime, 2) . 's' : '0s',
                ]);
            }

        } catch (\Throwable $e) {
            $totalTime = microtime(true) - $startTime;

            Log::error("UpdateUserLastSeenJob: Failed", [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'execution_time' => round($totalTime, 2) . 's',
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("UpdateUserLastSeenJob: Failed permanently after {$this->tries} attempts", [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
