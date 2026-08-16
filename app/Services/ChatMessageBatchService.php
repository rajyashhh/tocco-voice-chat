<?php

namespace App\Services;

use Modules\Chat\Entities\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatMessageBatchService
{
 
    private const BATCH_SIZE = 5;

 
    private const BATCH_SLEEP_MS = 100;

    /**

     * @param array $roomIds
     * @param int $excludeUserId 
     * @return array 
     */
    public function markMessagesAsReceivedInBatch(array $roomIds, int $excludeUserId): array
    {
        if (empty($roomIds)) {
            Log::channel('chat')->warning('markMessagesAsReceivedInBatch: empty room IDs');
            return [
                'success' => true,
                'total_rooms' => 0,
                'total_messages_updated' => 0,
                'batches_processed' => 0,
                'duration_ms' => 0,
            ];
        }

        $startTime = microtime(true);
        $totalUpdated = 0;
        $batchCount = 0;
        $failedBatches = [];
        $maxRetries = 3;

        try {
            foreach (array_chunk($roomIds, self::BATCH_SIZE) as $chunk) {
                $retryCount = 0;
                $updated = 0;
                
                while ($retryCount < $maxRetries) {
                    try {
                        $batchCount++;
                        $updated = 0;
                        foreach ($chunk as $roomId) {
                            $updated += DB::transaction(function () use ($roomId, $excludeUserId) {
                                return ChatMessage::where('chat_room_id', $roomId)
                                    ->where('user_id', '!=', $excludeUserId)
                                    ->where('status', 'sended')
                                    ->update(['status' => 'received']);
                            });
                        }

                        $totalUpdated += $updated;
                        break; // Success, exit retry loop

                    } catch (\Exception $e) {
                        $retryCount++;
                        
                        // Check if it's a deadlock error (1213)
                        if (strpos($e->getMessage(), '1213') !== false && $retryCount < $maxRetries) {
                            // Exponential backoff: 100ms, 200ms, 400ms
                            usleep(pow(2, $retryCount - 1) * 100 * 1000);
                            continue;
                        }
                        
                        // If max retries exceeded or not a deadlock, log and add to failed batches
                        $failedBatches[] = [
                            'batch_number' => $batchCount,
                            'rooms' => $chunk,
                            'error' => $e->getMessage(),
                            'retry_count' => $retryCount,
                        ];

                        Log::channel('chat')->error('Batch update failed', [
                            'batch_number' => $batchCount,
                            'rooms_in_batch' => count($chunk),
                            'error' => $e->getMessage(),
                            'retry_count' => $retryCount,
                        ]);
                        break;
                    }
                }

                if ($batchCount < ceil(count($roomIds) / self::BATCH_SIZE)) {
                    usleep(self::BATCH_SLEEP_MS * 1000);
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'success' => empty($failedBatches),
                'total_rooms' => count($roomIds),
                'total_messages_updated' => $totalUpdated,
                'batches_processed' => $batchCount,
                'failed_batches' => $failedBatches,
                'duration_ms' => round($duration, 2),
            ];

        } catch (\Exception $e) {
            Log::channel('chat')->critical('Critical error in batch update', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'total_rooms' => count($roomIds),
                'total_messages_updated' => $totalUpdated,
                'batches_processed' => $batchCount,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }

    /**
     * 
     * @param int $messageId 
     * @param string $status 
     * @return bool 
     */
    public function updateMessageStatus(int $messageId, string $status): bool
    {
        try {
            return DB::transaction(function () use ($messageId, $status) {
                $updated = ChatMessage::where('id', $messageId)
                    ->update(['status' => $status]);

                return $updated > 0;
            });
        } catch (\Exception $e) {
            Log::channel('chat')->error('Failed to update message status', [
                'message_id' => $messageId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 
     * @param int $roomId 
     * @param int $excludeUserId 
     * @param string $fromStatus 
     * @param string $toStatus 
     * @return int 
     */
    public function updateRoomMessagesStatus(
        int $roomId,
        int $excludeUserId,
        string $fromStatus = 'sended',
        string $toStatus = 'received'
    ): int {
        try {
            return DB::transaction(function () use ($roomId, $excludeUserId, $fromStatus, $toStatus) {
                return ChatMessage::where('chat_room_id', $roomId)
                    ->where('user_id', '!=', $excludeUserId)
                    ->where('status', $fromStatus)
                    ->update(['status' => $toStatus]);
            });
        } catch (\Exception $e) {
            Log::channel('chat')->error('Failed to update room messages status', [
                'room_id' => $roomId,
                'exclude_user_id' => $excludeUserId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 
     * @param array $roomIds 
     * @param int $daysOld 
     * @return array 
     */
    public function deleteOldMessagesInBatch(array $roomIds, int $daysOld = 30): array
    {
        if (empty($roomIds)) {
            return [
                'success' => true,
                'total_deleted' => 0,
                'batches_processed' => 0,
            ];
        }

        $startTime = microtime(true);
        $totalDeleted = 0;
        $batchCount = 0;

     

        try {
            foreach (array_chunk($roomIds, self::BATCH_SIZE) as $chunk) {
                try {
                    $batchCount++;
                    $deleted = DB::transaction(function () use ($chunk, $daysOld) {
                        return ChatMessage::whereIn('chat_room_id', $chunk)
                            ->where('created_at', '<', now()->subDays($daysOld))
                            ->delete();
                    });

                    $totalDeleted += $deleted;

                 

                    usleep(self::BATCH_SLEEP_MS * 1000);

                } catch (\Exception $e) {
                    Log::channel('chat')->error('Batch delete failed', [
                        'batch_number' => $batchCount,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $duration = (microtime(true) - $startTime) * 1000;

            Log::channel('chat')->info('Batch delete completed', [
                'total_deleted' => $totalDeleted,
                'batches_processed' => $batchCount,
                'duration_ms' => round($duration, 2),
            ]);

            return [
                'success' => true,
                'total_deleted' => $totalDeleted,
                'batches_processed' => $batchCount,
                'duration_ms' => round($duration, 2),
            ];

        } catch (\Exception $e) {
            Log::channel('chat')->critical('Critical error in batch delete', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'total_deleted' => $totalDeleted,
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ];
        }
    }
}
