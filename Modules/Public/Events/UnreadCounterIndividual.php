<?php

namespace Modules\Public\Events;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
class UnreadCounterIndividual implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public $type, $user, $counter;
    public function __construct($type, $user, $counter)
    {
        $this->type = $type;
        $this->user = $user;
        $this->counter = $counter;
    }


    public function broadcastOn()
    {
        return ['unread-' . $this->user?->id];
    }
    public function broadcastAs()
    {
        return 'UnreadCounterIndividual';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'counter'      => $this->counter ?? 1,
        ];
    }
}
