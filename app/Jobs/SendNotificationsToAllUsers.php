<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Http\Resources\Api\V1\GroupChatResource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationsToAllUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $text;
    protected $groupChatResource;

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
        $notificationsIdsChunks = User::withoutAppends()->where('notification_id', '!=', null)
            ->select(['id', 'notification_id'])
            ->orderByDesc('online')
            ->where('id', '!=', $this->user->id)
            ->limit(5000)
            ->get()
            ->unique('notification_id')
            ->chunk(800);

        foreach ($notificationsIdsChunks as $notificationsIds) {
            $notificationsIds = $notificationsIds->pluck('notification_id')->toArray();
            $title = ($this->user->name ?? '') . ' (' . config('app.name_en') .' Chat Group)';
            Common::send_firebase_notification(
                $notificationsIds,
                $title,
                ($this->text ?? ''),
                $this->groupChatResource,
                data: [
                    'title' => $title,
                    'sub-title' => ($this->text ?? '')
                ],
                messageType: 'group-chat'
            );
        }
    }
}
