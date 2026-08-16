<?php

namespace Modules\RoleRewards\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserHistoryReward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'receive_type',
        'rewardable_id',
        'rewardable_type',
        'extra',
        'sub_type',
        'is_deleted',
        

    ];
    protected $dates = ['deleted_at'];


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

    public function getReceiveNameAttribute()
    {
        // if (str_starts_with($this->receive_type, 'Role:')) {
        //     $id = (int) str_replace('Role:', '', $this->receive_type);
        //     static $rolesCache = [];
        //     if (!isset($rolesCache[$id])) {
        //         $rolesCache[$id] = \App\Models\Role::find($id)?->name ?? null;
        //     }
        //     return $rolesCache[$id] ?? $this->receive_type;
        // }
    
        // if (str_starts_with($this->receive_type, 'Milestone:')) {
        //     $id = (int) str_replace('Milestone:', '', $this->receive_type);
        //     static $milestonesCache = [];
        //     if (!isset($milestonesCache[$id])) {
        //         $milestonesCache[$id] = \Modules\Milestones\Entities\Milestone::find($id)?->name ?? null;
        //     }
        //     return $milestonesCache[$id] ?? $this->receive_type;
        // }
    
        // return $this->receive_type;
    }
    
}
