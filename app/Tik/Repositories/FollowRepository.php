<?php

namespace App\Tik\Repositories;

use App\Models\Follow;

class FollowRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Follow());
    }

    public function countFollows($userIds)
    {
        return $this->model->query()->where(fn($q) => $q->whereIn("followed_user_id", $userIds)
            ->orWhere(fn($q2) => $q2->whereIn("user_id", $userIds)->where("status", 1)))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
    }
    public function countFriends($userIds)
    {
        return $this->model->query()->where(fn($q) => $q->whereIn("followed_user_id", $userIds)->orWhereIn("user_id", $userIds))
            ->where("status", 1)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)->count();
    }

    public function deleteByFollowed($userId, $followedId)
    {
        return $this->model->query()->where('user_id', $userId)->where('followed_user_id', $followedId)->delete();
    }
}
