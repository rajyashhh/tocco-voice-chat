<?php

namespace Modules\RoomBoom\Jobs;

use App\Events\EndRoomBoomEvent;
use App\Jobs\SendRoomDataJob;
use App\Models\Room;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EndBoomPusherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $currentLevel;
    public $newTotal;
    public $userId;
    public $room;

    public function __construct($currentLevel, $newTotal, $userId, $room)
    {
        $this->currentLevel = $currentLevel;
        $this->newTotal = $newTotal;
        $this->userId = $userId;
        $this->room = gettype($room) == 'integer'? Room::find($room) : $room ;
    }

    public function handle(): void
    {
        info('in end room pusher job');

        $user = User::with('profile')->where('id', $this->userId)->first();

        $gift_data = [
            'room_id'           => $this->room->id,
            'room_uuid'         => $this->room->owner?->uuid ?: 0,
            'room_owner_id'     => $this->room->uid ?: 0,
            'is_password'       => (bool)(@$this->room->room_pass),
            'gift_price'        => $this->newTotal,
            'room_name'         => $this->room->room_name ?: '',
            "room_cover"        => $this->room->room_cover ?? '',
            "room_background"   => $this->room->final_room_image ?? '',
            "room_mode"         => $this->room->mode,
            'roomBoomLevel'     => $this->currentLevel->level,
            'duration'          => 30,
            'user_image'        => $user->profile->avatar ?? '',
            'room_type'        => $this->room->type,
        ];

        event(new EndRoomBoomEvent($gift_data));

        $inRoom = json_encode(['messageContent' => ['message' => 'end_room_boom', 'endData' => $gift_data]]);
        dispatchJobToQueue(new SendRoomDataJob((int) $this->room->id, (int) $this->userId, [$inRoom]), 'heavyProcessing');
    }
}
