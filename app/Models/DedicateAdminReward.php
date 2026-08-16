<?php

namespace App\Models;

use App\Traits\TimestampsWithTimezone;
use Illuminate\Database\Eloquent\Model;
use Modules\Country\Entities\SuperAdminReward;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DedicateAdminReward extends Model
{
    use HasFactory, TimestampsWithTimezone;
    protected $table = 'dedicate_admin_rewards';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reward()
    {
        return $this->belongsTo(SuperAdminReward::class, 'reward_id');
    }
}
