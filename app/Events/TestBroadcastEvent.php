<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // ⭐ Changed to ShouldBroadcastNow
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestBroadcastEvent implements ShouldBroadcastNow // ⭐ Immediate broadcast - no queue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $config;

    public function __construct($pusherConfig)
    {
        $this->message = 'Test broadcast event with database config';
        $this->config = [
            'app_id' => $pusherConfig['app_id'],
            'app_key' => $pusherConfig['app_key'],
            'cluster' => $pusherConfig['app_cluster'],
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('test-channel'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'config' => $this->config,
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'test.broadcast';
    }
}

