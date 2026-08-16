<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupChat implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(private readonly array $data) {
        info('hi');

    }


    public function broadcastOn()
    {
        return ['group-chat'];
    }

    public function broadcastAs()
    {
        return 'getGroupChatBloc';
    }

    public function broadcastWith() : array
    {
        return (array) $this->data;
    }
}
