<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomBoomRewardsEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rewardData;
    protected $roomId;
    public function __construct($rewardData, $roomId)
    {
        $this->rewardData = $rewardData;
        $this->roomId = $roomId;
    }

    public function broadcastOn(): Channel
    {
        return new PresenceChannel('room.boom.rewards.' . $this->roomId);
    }

    public function broadcastAs(): string
    {
        return 'room_boom_rewards';
    }

    public function broadcastWith(): array
    {
        return [
            'roomBoomLevel' => $this->rewardData['roomBoomLevel'],
            'duration'      => $this->rewardData['duration'],
            'winners'       => collect($this->rewardData['winners'])
                ->mapWithKeys(fn($winner) => [
                    $winner['user_id'] => [
                        'image' => $winner['image'],
                        'image_type' => $winner['image_type'],
                    ]
                ]),
        ];
    }

    public function broadcastQueue()
    {
        return 'roomBoomRewards';
    }
}
