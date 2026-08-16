<?php

namespace Modules\RoomCup\Entities;

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\RoomBoom\Entities\TotalRoomGift;

class RoomCupReward extends Model
{
    protected $table = 'room_cup_rewards';

    protected $fillable = [
        'room_id',
        'user_id',
        'total_room_gift_id',
        'target_id',
        'amount',
        'type',
    ];

    public function gift(): BelongsTo
    {
        return $this->belongsTo(TotalRoomGift::class, 'total_room_gift_id');
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
