<?php
namespace App\Events;

use App\Models\AdminNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\AdminNotification $notification
     */
    public function __construct(AdminNotification $notification)
    {
        $this->notification = $notification;
    }

    /**
     * The name of the channel on which the event is broadcast.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        // قناة عامة بدل الخاصة
        return new Channel('admin.notifications');
    }

    /**
     * اسم الحدث عند البث
     */
    public function broadcastAs()
    {
        return 'AdminNotificationCreated';
    }
    
    /**
     * البيانات المرسلة عند البث
     *
     * @return array
     */
    public function broadcastWith()
    {

        return [
            'id'        => $this->notification->id,
            'title'     => $this->notification->title,
            'message'   => $this->notification->message,
            'data'      => $this->notification->data,
            'is_read'   => $this->notification->is_read,
            'created_at'=> $this->notification->created_at->toDateTimeString(),
        ];
    }
}
