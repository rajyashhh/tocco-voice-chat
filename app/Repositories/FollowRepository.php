<?php

namespace App\Repositories;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Query\JoinClause;

class FollowRepository
{
    public function findFollow($userId, $followedUserId)
    {
        return Follow::where('user_id', $userId)
            ->where('followed_user_id', $followedUserId)
            ->first();
    }

    public function createFollow($data)
    {
        try {
            return Follow::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            // uniq_follow_pair (added 2026-06-12): a concurrent double-tap
            // used to silently insert a duplicate row; now the DB refuses it —
            // treat the race as idempotent and return the existing follow.
            if (($e->errorInfo[1] ?? null) === 1062) {
                return Follow::where('user_id', $data['user_id'])
                    ->where('followed_user_id', $data['followed_user_id'])
                    ->first();
            }
            throw $e;
        }
    }

    public function updateFollowStatus($follow, $status)
    {
        $follow->status = $status;
        $follow->save();
    }

    public function deleteFollow($userId, $followedUserId)
    {
        return Follow::where('user_id', $userId)
            ->where('followed_user_id', $followedUserId)
            ->delete();
    }

    public function findUserById($userId)
    {
        return User::find($userId);
    }

    public function getByFollowed($userId)
    {
        return Follow::query()->where('user_id', $userId)->whereHas('followed')->with('followed', function ($query) {
            $query->with([
                'room' => function ($query) {
                    return $query->withoutAppends()->select(['id', 'room_pass', 'uid']);
                },
                'followPacks',
                'profile',
                'ware',
                'UserVip'
            ]);
        })->orderByDesc('id')->paginate(15);
    }

    public function getFollowing(User $user, array $with = [], $keyword)
    {

        if (empty($with)) {
            $with = [
                'room' => function ($query) {
                    return $query->withoutAppends()->select(['id', 'room_pass', 'uid']);
                },
                'followPacks',
                'profile',
                'ware',
                'UserVip',
                'manager'
            ];
        }
        return $user->following()->with($with)->fitterByUuid($keyword)->paginate(12); // Set pagination limit

    }

    // Get paginated list of users that are following the current user
    public function getFollowers(User $user, array $with = [], $keyword)
    {
        return $user->followerss()->with($with)->fitterByUuid($keyword)->paginate(12);
    }

    // Get paginated list of mutual followers (friends)
    public function getFriends(User $user, array $with = [], $keyword)
    {
        return $user->friends()->with($with)->fitterByUuid($keyword)->paginate(10);
    }

    public function getByFollower($userId)
    {
        return Follow::query()->where('followed_user_id', $userId)->whereHas('follower')->with('follower', function ($query) {
            $query->with([
                'room' => function ($query) {
                    return $query->withoutAppends()->select(['id', 'room_pass', 'uid']);
                },
                'followPacks',
                'profile',
                'ware',
                'UserVip'
            ]);
        })->orderByDesc('id')->paginate(15);
    }

    public function getByFriends($userId)
    {
        return Follow::query()->whereHas('followed')->whereHas('follower')->join('follows as f1', function (JoinClause $join) {
            $join->on('follows.user_id', '=', 'f1.followed_user_id')
                ->on('f1.user_id', '=', 'follows.followed_user_id');
        })->where('follows.user_id', $userId)->with('follower', function ($query) {
            $query->with([
                'room' => function ($query) {
                    return $query->withoutAppends()->select(['id', 'room_pass', 'uid']);
                },
                'followPacks',
                'profile',
                'ware',
                'UserVip'
            ]);
        })->orderByDesc('follows.id')->paginate(15);
    }

    public function getFollow($userId)
    {
        $followId = Follow::where('followed_user_id', $userId)
            ->whereNotIn('user_id', function ($query) use ($userId) {
                $query->select('followed_user_id')
                    ->from('follows')
                    ->where('user_id', $userId);
            })->pluck('user_id');
        return User::whereIn('id', $followId)->paginate(15);
    }

    public function getFollowedIds($userId)
    {
        return Follow::query()->where('user_id', $userId)->pluck('followed_user_id')->toArray();
    }

    public function checkFollowing($authId, $followId)
    {
        return Follow::query()->where('user_id', $authId)->where('followed_user_id', $followId)->exists();
    }

    public function checkFollower($authId, $followId)
    {
        return Follow::query()->where('user_id', $followId)->where('followed_user_id', $authId)->exists();
    }

    public function getFollowedUserIds($userId)
    {
        return Follow::where('user_id', $userId)->pluck('followed_user_id')->toArray();
    }
}
