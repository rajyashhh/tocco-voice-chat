<?php

namespace Modules\RoomBoom\Entities;

use App\Models\Room;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\RoomCup\Entities\RoomCupReward;

class TotalRoomGift extends Model
{
    protected $fillable = ['room_id', 'current_total','number_of_visitors'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomBooms(): HasMany
    {
        return $this->hasMany(RoomBoom::class, 'total_room_gift_id');
    }

    public function ownerRewards()
    {
        return $this->hasMany(RoomCupReward::class, 'total_room_gift_id');
    }

}
