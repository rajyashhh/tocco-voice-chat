<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Http\Resources\Api\V1\GroupChatResource;
use App\Models\User;
use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SendShareGroupChatNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $text;
    protected $groupChatResource;

    public $timeout = 300; 

    public function __construct(User $user, ?string $text, array $groupChatResource)
    {
        $this->user = $user;
        $this->text = $text;
        $this->groupChatResource = $groupChatResource;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
    

            if ($this->text && str_starts_with($this->text, 'share_room:')) {
                $this->handleShareRoom();
                return;
            }

        } catch (\Throwable $e) {
            Log::error('SendShareGroupChatNotificationJob: Exception in handle', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle share room notification with image
     */
    protected function handleShareRoom(): void
    {
        try {
            $parts = explode(':', $this->text);
            
            $roomId = $parts[3] ?? null;

            if (!$roomId) {
                Log::warning('SendShareGroupChatNotificationJob: Invalid room ID in text', [
                    'text' => $this->text,
                ]);
                return;
            }

            // ✅ Add error handling for ChatRoom not found
            try {
                $room = Room::select('id', 'room_cover', 'mode','is_pk_custom')->findOrFail($roomId);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::warning('SendShareGroupChatNotificationJob: ChatRoom not found', [
                    'room_id' => $roomId,
                    'user_id' => $this->user->id,
                ]);
                return;
            }


            $roomImage = $room->room_cover ?? $room->final_room_image ?? $this->groupChatResource['image_url'] ?? '';
            $userLang = $this->user->lan ?? 'en';
            $translatedMessage = __('share_room_message', [], $userLang);

            if (!empty($roomImage)) {
                $roomImage = getImagePath($roomImage);
            }
            
            $notificationsIdsChunks = User::withoutAppends()
                ->whereNotNull('notification_id')
                ->where('id', '!=', $this->user->id)
                ->select(['notification_id', \DB::raw('MAX(lan) as lan')])
                ->groupBy('notification_id')
                ->orderByDesc(\DB::raw('MAX(online)'))
                ->limit(5000)
                ->cursor()
                ->chunk(800);

            $totalSent = 0;
            $totalFailed = 0;

            foreach ($notificationsIdsChunks as $chunkIndex => $notificationsIds) {
                $this->sendNotificationBatch($notificationsIds, $roomId, $roomImage, $translatedMessage, $totalSent, $totalFailed);
            }

        
        } catch (\Throwable $e) {
            Log::error('SendShareGroupChatNotificationJob: Exception in handleShareRoom', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     */
    protected function sendNotificationBatch($notificationsIds, $roomId, $roomImage, $translatedMessage, &$totalSent, &$totalFailed): void
    {
        $notificationIds = $notificationsIds->pluck('notification_id')->toArray();
        
        if (empty($notificationIds)) {
            return;
        }

        try {
            $title = ($this->user->name ?? '') . ' (' . config('app.name_en') . ')';
            
            $result = Common::send_firebase_notification_with_room_image(
                $notificationIds,
                $title,
                $translatedMessage,
                $roomImage,
                $roomId,
                data: [
                    'title' => $title,
                    'sub-title' => $translatedMessage,
                    'room_id' => $roomId,
                    'room_image' => $roomImage
                ],
                messageType: 'share-room',
                user: $this->user
            );

            if ($result) {
                $totalSent += count($notificationIds);
            } else {
                $totalFailed += count($notificationIds);
            }
        } catch (\Throwable $e) {
            $totalFailed += count($notificationIds);
            Log::error('SendShareGroupChatNotificationJob: Failed to send batch', [
                'batch_size' => count($notificationIds),
                'error' => $e->getMessage()
            ]);
        }
    }
}
