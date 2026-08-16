<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreWallets extends Model
{
    use HasFactory, TimestampsWithTimezone;

    protected $guarded = [];

    protected $casts = ['updated_at' => 'datetime:Y-m-d H:i:s'];

    protected $appends = ['update_for_human'];

    public function getUpdateForHumanAttribute(): string
    {

        return Carbon::parse(@$this?->updated_at)->diffForHumans() ?? '';
    }
}
