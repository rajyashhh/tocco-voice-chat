<?php
namespace Modules\Country\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Country\Entities\SuperAdminNotification;

class SuperAdminNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;
    public $translatedTitle;
    public $translatedMessage;
    public $previewUrl;

    public function __construct(
        SuperAdminNotification $notification,
        string $translatedTitle,
        string $translatedMessage,
        ?string $previewUrl = null
    ) {
        $this->notification = $notification;
        $this->translatedTitle = $translatedTitle;
        $this->translatedMessage = $translatedMessage;
        $this->previewUrl = $previewUrl;
    }

    public function broadcastOn()
    {
        return new Channel('superAdmin.notifications.' . $this->notification->super_admin_id);
    }

    public function broadcastAs()
    {
        return 'SuperAdminNotificationCreated';
    }

    public function broadcastWith()
    {
        return [
            'id'        => $this->notification->id,
            'title'     => $this->translatedTitle,
            'message'   => $this->translatedMessage,
            'data'      => $this->notification->data,
            'is_read'   => $this->notification->is_read,
            'super_admin_id' => $this->notification->super_admin_id,
            'created_at'=> $this->notification->created_at->toDateTimeString(),
            'preview_url' => $this->previewUrl,
        ];
    }
}
