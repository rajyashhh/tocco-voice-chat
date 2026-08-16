<?php

namespace App\Traits\Families;

use App\Models\FamilyUser;
use App\Models\User;

trait ResourceTrait
{


    public function amMember($userId = null): bool
    {
        if (!$userId) $userId = \Auth::id();
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function amOwner($userId = null): bool
    {
        if (!$userId) $userId = \Auth::id();
        return @$this->user_id == $userId;
    }

    public function usersRequests()
    {
        return $this->hasManyThrough(User::class, FamilyUser::class, 'family_id', 'family_id')->where('family_user.status', 0);
    }

    public function getUsersRequestsCountAttribute()
    {
        $usersRequestsCount = @$this->attributes['users_requests_count'];
        return !is_null($usersRequestsCount) ? $usersRequestsCount : $this->usersRequests()->count();
    }

}
