<?php

namespace Modules\Public\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class UnreadCounterGroup implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $type, $counter;
    public function __construct($type, $counter)
    {
        $this->type = $type;
        $this->counter = $counter;
    }


    public function broadcastOn()
    {
        return ['counter-chanel'];
    }
    public function broadcastAs()
    {
        return 'UnreadCounterGroup';
    }

    public function broadcastWith() : array
    {
        return [
            'type' => $this->type,
            'counter'      => $this->counter ?? 1,
        ];
    }
}
