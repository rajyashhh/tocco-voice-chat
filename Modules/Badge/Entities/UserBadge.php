<?php

namespace Modules\Badge\Entities;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;

class UserBadge extends Model
{
    protected $guarded = [];

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'auth_id');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        });
    }
}
