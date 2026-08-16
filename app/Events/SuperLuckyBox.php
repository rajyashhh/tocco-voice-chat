<?php

namespace App\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class SuperLuckyBox implements ShouldBroadcastNow
{
    use SerializesModels, Dispatchable, InteractsWithSockets;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $superLuckyBox;
    public function __construct($superLuckyBox)
    {
        $this->superLuckyBox = $superLuckyBox;
        
    }


    public function broadcastOn()
    {
        return ['super-lucky-box-chanel'];
    }
    public function broadcastAs()
    {
        return 'superLuckBox';
    }

    public function broadcastWith() : array
    {
        return [
            (array) $this->superLuckyBox,
        ];
    }
}