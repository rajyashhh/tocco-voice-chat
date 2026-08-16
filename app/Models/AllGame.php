<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllGame extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $casts = [
        'is_enable' => 'boolean',
    ];

    public function coinGameUser()
    {
        return $this->hasMany(CoinGameUser::class, 'game_id');
    }

    public function getInRoomAttribute($value)
    {
        return $value === null ? 0 : $value;
    }

    /**
     * Backward-compatible aliases for the legacy misspelled attribute names
     * (hight / hight_image). The DB columns are now height / height_image, but
     * any caller still writing the old keys (e.g. external UTD panel payloads)
     * keeps working: reads/writes are transparently routed to the new columns.
     */
    public function getHightAttribute()
    {
        return $this->attributes['height'] ?? null;
    }

    public function setHightAttribute($value): void
    {
        $this->attributes['height'] = $value;
    }

    public function getHightImageAttribute()
    {
        return $this->attributes['height_image'] ?? null;
    }

    public function setHightImageAttribute($value): void
    {
        $this->attributes['height_image'] = $value;
    }
}
