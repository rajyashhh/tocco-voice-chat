<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

use Illuminate\Queue\SerializesModels;

// Broadcast synchronously inside the (already queued) AllOpeningRoomsZegoRequest
// worker: the Centrifugo publish is capped (3s timeout + retry, failures
// swallowed), so there is no second queue hop that could die on a missing
// broadcast worker — the global banner fires the instant the job runs.
class BannerEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;
    public string $channel;
    public function __construct($data)
    {
        $this->data = $data;
        $this->channel = $data['messageContent']['event'];

  
    }

    public function broadcastOn()
    {
        return new Channel($this->channel);
    }

    public function broadcastAs()
    {
        return $this->channel;
    }

    public function broadcastWith()
    {
        return $this->data;
    }
}
