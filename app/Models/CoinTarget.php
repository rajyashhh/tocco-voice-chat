<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinTarget extends Model
{
    use HasFactory, TimestampsWithTimezone;

    public $table = 'coins_targets';

    protected $guarded = [];

    public function gifts()
    {
        return $this->hasMany(CoinTargetGift::class, 'coin_target_id');
    }
}
