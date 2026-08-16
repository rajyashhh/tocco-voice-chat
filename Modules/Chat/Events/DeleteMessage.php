<?php

namespace Modules\Chat\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class DeleteMessage implements ShouldBroadcastNow
{
    use SerializesModels;

    public $message_id ;
    public $user2 ;
    public $check_room ;
    public function __construct($message_id ,$user2 ,$check_room)
    {
        $this->message_id = $message_id;
        $this->user2 = $user2;
        $this->check_room = $check_room;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn()
    {
        return ["user-$this->user2", 'conversation-'.$this->check_room->id];
    }

    public function broadcastAs()
    {
        return 'delete-message';
    }

    public function broadcastWith() : array
    {
        return ['message_id' => $this->message_id];
    }
}
