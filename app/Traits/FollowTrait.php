<?php

namespace App\Traits;

use App\Models\Follow;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait FollowTrait
{

    public function friends_ids()
    {
        return $this->friends()->pluck('users.id');
    }

    public function isFriends()
    {
        return $this->friends()->where("users.id", auth()->id())->exists();
    }

    public function followeds_ids()
    {
        return Follow::query()->whereHas('followed')->where('user_id', $this->id)->orderByDesc('created_at')->pluck('followed_user_id');
    }

    public function rooms_uids()
    {
        return Room::query()->where('room_status', 1)->where('is_afk', 1)->pluck('uid');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'user_id', 'followed_user_id')
            ->withPivot('status', 'created_at')
            ->withTimestamps()
            ->orderBy('follows.created_at', 'desc');
    }

    // Define the users that are following this user
    public function followerss(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followed_user_id', 'user_id')
            ->withPivot('status', 'created_at')
            ->withTimestamps()
            ->orderBy('follows.created_at', 'desc');
    }

    // Define mutual followers as friends
    // public function friends(): BelongsToMany
    // {
    //     return $this->following()
    //         ->wherePivot('status', 1) // Active status for mutual relationships
    //         ->whereHas('followerss', function ($query) {
    //             $query->where('user_id', $this->id);
    //         })
    //         ->orderBy('follows.created_at', 'desc');
    // }

    public function friends()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'user_id',
            'followed_user_id'
        )->whereIn('followed_user_id', function ($query) {
            $query->select('user_id')
                ->from('follows')
                ->where('followed_user_id', $this->id)
                ->where('status', 1);
        })->wherePivot('status', 1)->orderBy('follows.created_at', 'desc');
    }

    public function friendRelations()
    {
        return $this->belongsToMany(
            User::class,
            'follows',
            'user_id',
            'followed_user_id'
        )
        ->wherePivot('status', 1)
        ->whereExists(function ($query) {
            $query->selectRaw(1)
                ->from('follows as f2')
                ->whereColumn('f2.user_id', 'follows.followed_user_id')
                ->whereColumn('f2.followed_user_id', 'follows.user_id')
                ->where('f2.status', 1);
        })
        ->orderBy('follows.created_at', 'desc');
    }


    public function friendsFollowedId()
    {
        $friendsIds = $this->belongsToMany(
            User::class,
            'follows',
            'user_id',
            'followed_user_id'
        )
        ->whereIn('followed_user_id', function ($query) {
            $query->select('user_id')
                  ->from('follows')
                  ->where('followed_user_id', $this->id);
        })
        ->wherePivot('status', 1)
        ->pluck('followed_user_id'); // استخراج معرفات الأصدقاء

        $followedIds = Follow::where('user_id', $this->id)
            ->pluck('followed_user_id'); // استخراج معرفات المستخدمين الذين يتابعهم

        return $friendsIds->merge($followedIds)->unique(); // دمج النتائج وإزالة التكرارات
    }

    // Assume we have a relationship to check if the user is being followed
    public function followedByAuthUser()
    {
        return $this->hasOne(Follow::class, 'followed_user_id', 'id')
            ->where('user_id', auth()->id());
    }
    public function followerByAuthUser()
    {
        return $this->hasOne(Follow::class, 'user_id', 'id')
            ->where('followed_user_id', auth()->id());
    }

    // Accessor for is_follow property
    public function getIsFollowAttribute()
    {
        // Check if the authenticated user follows this user
        return $this->followedByAuthUser()->exists();
    }
    public function getIsFollowedAttribute()
    {
        // Check if the authenticated user follows this user
        return $this->followerByAuthUser()->exists();
    }


    public function numberOfFans()
    {
        return $this->followers()->count();
    }

    public function numberOfFollowings()
    {
        return $this->following()->count();
    }

    public function numberOfFriends()
    {
        return $this->friends()->count();
    }

    public function onRoomFolloweds()
    {
        return self::query()->whereIn('id', $this->followeds_ids())->whereIn('id', $this->rooms_uids())->get();
    }
}
