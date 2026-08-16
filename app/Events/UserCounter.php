<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserCounter implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $type ;
    public $count ;
    public $user_id ;
    public function __construct($type ,$count ,$user_id)
    {
        $this->type = $type;
        $this->count = $count;
        $this->user_id = $user_id;
    }

    public function broadcastOn()
    {
        return ['counter-user-'.$this->user_id];
    }

    public function broadcastAs()
    {
        return 'counter-user';
    }

    public function broadcastWith() : array
    {
        return ['type'=>$this->type,"count" =>$this->count];
    }
}
