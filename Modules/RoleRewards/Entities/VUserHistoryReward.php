<?php

namespace Modules\RoleRewards\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VUserHistoryReward extends Model
{
    
    protected $fillable = [
        'user_id',
        'receive_type',
        'rewardable_id',
        'rewardable_type',
        'extra',
        'sub_type',
        'is_deleted'

    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function rewardable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public $timestamps = false;






    
}
