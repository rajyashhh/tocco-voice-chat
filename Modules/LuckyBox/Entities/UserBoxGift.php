<?php

namespace Modules\LuckyBox\Entities;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;

class UserBoxGift extends Model
{
    use TimestampsWithTimezone;

    protected $table = 'user_box_gifts';

    protected $guarded = [];
    protected static function booted()
    {
        static::created(function ($gift) {
            if (($gift->user_id ?? null) && ($gift->coins ?? 0) > 0) {

            }
        });
    }
}
