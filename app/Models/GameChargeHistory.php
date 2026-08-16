<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameChargeHistory extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {

            $model->admin_id = Auth::id();
        });
    }
}
