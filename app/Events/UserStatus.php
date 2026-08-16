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

class UserStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $can_play ;
    public $show_invite_code ;
    public $user_id ;
    public function __construct($can_play ,$show_invite_code ,$user_id)
    {   
        $this->can_play = $can_play;
        $this->show_invite_code = $show_invite_code;
        $this->user_id = $user_id;

   
    }

    public function broadcastOn()
    {
   
        return ['status-user-'.$this->user_id];
    }

    public function broadcastAs()
    {
        return 'status-user';
    }

    public function broadcastWith() : array
    {
   
        
        return [
            'show_invite_code'=>$this->show_invite_code,
            "can_play" =>$this->can_play
        ];
    }
}
