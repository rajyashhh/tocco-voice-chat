<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\TaskStream\Entities\PkSession;
use Modules\TaskStream\Entities\TaskStream;
use Modules\TaskStream\Entities\TaskStreamRoom;
use Modules\Vip\Entities\Vip;
use Modules\Chat\Entities\ChatMessage;
use Modules\LuckyBox\Entities\BoxUse;
use App\Traits\TimestampsWithTimezone;
use Modules\LuckyBox\Traits\RoomBoxes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\RoomBoom\Entities\TotalRoomGift;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @method static withoutAppends()
 */
class Room extends Model
{
    use TimestampsWithTimezone;
    use RoomBoxes;

    /*
 * To enable and disable observer saving and updating methods
 */
    public $enableSaving = true;

    public static $withoutAppends = false;

    protected $guarded = [];

    protected $appends = ['lang', 'country'];

    protected $casts = [
        'is_pk' => 'boolean',
        'is_comment_closed' => 'boolean',
        'is_live' => 'boolean',
        'is_broadcasting' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uid');
    }

    public function roomLevel()
    {
        return $this->belongsTo(Vip::class, 'level_id');
    }

    public function roomSalary()
    {
        return $this->hasMany(RoomSalary::class, 'room_id');
    }

    public function getSalaryAttribute()
    {
        $salary = RoomSalary::query()->where('room_id', $this->id)->where('is_paid', 0)->sum(DB::raw('salary - cut_amount'));

        return $salary;
    }

    public function EnterRoom()
    {
        return $this->hasOne(EnteredRoom::class, 'rid');
    }

    public function scopeWithoutAppends(Builder $query): Builder
    {
        self::$withoutAppends = true;

        return $query;
    }

    //    public function getRoomBackgroundAttribute($val){
    //        if (self::$withoutAppends){
    //            return;
    //        }
    //        return @Background::query ()->where ('id',$val)->first ()->img;
    //    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function taskStream()
    {
        return $this->hasOne(TaskStream::class);
    }

    public function taskStreamRoom()
    {
        return $this->hasOne(TaskStreamRoom::class);
    }

    public function game()
    {
        return $this->belongsTo(AllGame::class, 'game_id');
    }

    public function microphones(): HasMany
    {
        return $this->hasMany(RoomMicrophone::class);
    }

    // public function getLangAttribute()
    // {
    //     if (self::$withoutAppends) {
    //         return;
    //     }

    //     return @$this->owner->country->language;
    // }

    public function getLangAttribute()
    {
        if (self::$withoutAppends) {
            return null;
        }

        // prevent lazy loading
        if (! $this->relationLoaded('owner')) {
            return null;
        }

        if (! $this->owner || ! $this->owner->relationLoaded('country')) {
            return null;
        }

        return $this->owner->country->language ?? null;
    }

    // public function getCountryAttribute()
    // {
    //     if (self::$withoutAppends) {
    //         return;
    //     }
    //     $country = @$this->owner->country;

    //     return $country;
    // }

    public function getCountryAttribute()
    {
        if (self::$withoutAppends) {
            return null;
        }

        // prevent lazy-loading queries
        if (! $this->relationLoaded('owner')) {
            return null;
        }

        $owner = $this->owner;

        if (! $owner || ! $owner->relationLoaded('country')) {
            return null;
        }

        return $owner->country;
    }


    public function myClass()
    {
        return $this->belongsTo(RoomCategory::class, 'room_class')->select('name', 'img');
    }

    public function myType()
    {
        return $this->belongsTo(RoomCategory::class, 'room_type')->select('name', 'img');
    }

    public function pks()
    {
        return $this->hasMany(Pk::class, 'room_id', 'id');
    }


    public function gifts()
    {
        return $this->hasMany(GiftLog::class, 'roomowner_id', 'uid');
    }

    public function topUserGift()
    {
        return $this->hasOne(GiftLog::class, 'roomowner_id', 'id')
            ->selectRaw('SUM(giftPrice) as exp, sender_id, roomowner_id')
            ->whereHas('sender')
            ->groupBy('sender_id', 'roomowner_id')
            ->orderByDesc('exp');
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class, 'room_type');
    }

    public function family()
    {
        return $this->belongsTo(Family::class, 'uid', 'user_id');
    }

    public function lastPk()
    {
        return $this->hasOne(Pk::class, 'room_id', 'id')->where('status', 1)->where('end_at', '>=', now())->orderByDesc('id');
    }
    public function lastPkSession()
    {
        return $this->hasOneThrough(
            PkSession::class,
            TaskStream::class,
            'room_id',
            'task_stream_id'
        )->where('status', 1)->where('ends_at', '>=', now())->orderByDesc('id');
    }

    public function getSessionStringAttribute()
    {
        return numToString($this->session);
    }

    //    public function getMicrophoneAttribute()
    //    {
    //        $microphoneWithOldSeat = array_key_exists('microphone', $this->attributes) ? $this->attributes['microphone'] : '';
    //        $microphoneWithOldSeat = explode(',', $microphoneWithOldSeat);
    //        $array = array_map(function ($id) {
    //            return explode('#', $id)[0];
    //        }, $microphoneWithOldSeat);
    //
    //        return implode(',', $array);
    //    }

    // public function getMicrophoneAttribute()
    // {
    //     return $this->microphones()
    //         ->orderBy('position')
    //         ->get()
    //         ->map(function ($mic) {
    //             $userId = $mic->user_id ?? 0;
    //             $status = $mic->status ?? 0;

    //             if ($userId > 0) {
    //                 return "{$userId}#{$status}";
    //             } else {
    //                 return (string)$status;
    //             }
    //         })
    //         ->implode(',');
    // }

    public function getMicrophoneAttribute()
    {
        // Use already-loaded relation
        $microphones = $this->relationLoaded('microphones')
            ? $this->microphones
            : collect(); // or $this->microphones()->get() if you REALLY need fallback

        return $microphones
            ->sortBy('position')
            ->map(function ($mic) {
                $userId = $mic->user_id ?? 0;
                $status = $mic->status ?? 0;

                return $userId > 0
                    ? "{$userId}#{$status}"
                    : (string) $status;
            })
            ->implode(',');
    }

    public function getMicrophoneOnlyUsersAttribute()
    {
        return  $this->attributes['microphone'] ?? '';
    }
    public function getAllMicrophoneAttribute()
    {
        return  $this->attributes['microphone'] ?? '';
    }

    public function getMainMicrophoneAttribute()
    {
        $microphoneWithOldSeat = array_key_exists('microphone', $this->attributes) ? $this->attributes['microphone'] : '';
        $microphoneWithOldSeat = explode(',', $microphoneWithOldSeat);
        $array = array_map(function ($id) {
            $arr = collect(explode('#', $id));
            $value = $arr->last();

            return $value > 0 ? 0 : $value;
        }, $microphoneWithOldSeat);

        return implode(',', $array);
    }

    public function getCountRoomSocketAttribute()
    {
        $ids = explode(',', $this->room_visitor);
        /*$countPacks = Pack::query()->whereIn('user_id', $ids)
            ->where('is_used', 1)
            ->where('type', 17)
            ->where(function ($q) {
                $q->where('packs.expire', 0)->orWhere('packs.expire', '>=', time());
            })
            ->count();

        foreach ($ids as $indes => $id) {
            if ($id === '' || $id < 0) {
                unset($ids[$indes]);
            }
        }*/

        return count($ids);
    }

    public function getCountRoomSocketV2Attribute()
    {
        // Optimize: Use DB count if relation not loaded to avoid N+1 query
        if (!$this->relationLoaded('roomVisitors')) {
            return RoomVisitor::where('room_id', $this->id)->count();
        }

        return $this->roomVisitors->count();
    }

    public function roomVisitors(): HasMany
    {
        return $this->hasMany(RoomVisitor::class, 'room_id');
    }

    public function roomVisitorUsers(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,        // Final model
            RoomVisitor::class, // Intermediate model
            'room_id',          // Foreign key on room_visitors table
            'id',               // Foreign key on users table
            'id',               // Local key on rooms table
            'user_id'           // Local key on room_visitors table
        );
    }

    public function getRoomVisitorAttribute(): string
    {
        $usersIds = $this->roomVisitors->pluck('user_id')->toArray();

        return count($usersIds) > 0 ? implode(',', $usersIds) : '';
    }

    public function getRoomVisitorNewAttribute(): string
    {
        return $this->visitor_ids ?? '';
    }

    public function topUser()
    {
        return $this->belongsTo(User::class, 'top_user_id');
    }

    public function boxUse()
    {
        return $this->hasMany(BoxUse::class, 'room_id');
    }

    public function backgroundImage()
    {
        return $this->hasOneThrough(
            RequestBackgroundImage::class,
            User::class,
            'id',
            'owner_room_id',
            'uid',
            'id'
        )->where('request_background_images.status', 1)->where(function ($q) {
            $q->where('expair', '>=', now()->timestamp)
                ->orWhere('expair', 0);
        })->orderByDesc('id');
    }



    public function getVisitorsImages()
    {
        // Optimize: Check if relation is loaded to avoid N+1 query
        if (!$this->relationLoaded('roomVisitorUsers')) {
            // Load the relation with profile if not already loaded
            $this->load('roomVisitorUsers.profile');
        }

        return $this->roomVisitorUsers->pluck('profile.avatar');
    }

    public function background()
    {
        return $this->belongsTo(Background::class, 'room_background');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_room_id', 'id');
    }


    public function getFinalRoomImageAttribute()
    {
        if ($this->is_pk_custom && $this->mode === 3) {
            return PK_IMAGE;
        }
        if ($this->mode === 8) {
            return BaCKGROUND_IMAGE_MODE_8;
        }
        // dd($this->background?->img);
        return $this->backgroundImage?->img
            ?? $this->background?->img
            ?? $this->defaultBackground?->img
            ?? request()->default_background;
    }

    public function defaultBackground()
    {
        return $this->hasOne(Background::class, 'id')->where('enable', 1)->orderBy('id');
    }

    public function getModeAttribute($value)
    {
        return $value === 0 ? 3 : $value;
    }

    public function scopeWithoutVisitorsAndActiveMic($query)
    {
        return $query->whereDoesntHave('roomVisitors')
            ->where('microphone', '!=', '0,0,0,0,0,0,0,0,0,0');
    }

    public function bans()
    {
        return $this->hasMany(BanRoom::class);
    }

    protected function getAdminsAttribute()
    {
        return explode(',', $this->room_admin);
    }

    public function totalRoomGifts(): HasMany
    {
        return $this->hasMany(TotalRoomGift::class, 'room_id');
    }

    public function admins()
    {
        return $this->hasMany(User::class, 'id', 'room_admin');
    }

    /**
     * Get administrators from the new normalized table
     */
    public function administrators()
    {
        return $this->hasMany(\App\Models\RoomAdministrator::class, 'room_id');
    }

    /**
     * Get blacklisted users from the new normalized table
     */
    public function blacklistedUsers()
    {
        return $this->hasMany(\App\Models\RoomBlacklist::class, 'room_id')
            ->valid();
    }


    protected static $microphoneCache = [];

    public static function cacheMicrophoneUsers($ids)
    {
        if (empty($ids)) return collect();

        $missingIds = array_diff($ids, array_keys(self::$microphoneCache));

        if (!empty($missingIds)) {
            $users = User::whereIn('id', $missingIds)
                ->with('profile:id,user_id,avatar')
                ->get()
                ->keyBy('id');

            foreach ($users as $id => $user) {
                self::$microphoneCache[$id] = $user;
            }
        }

        return collect(self::$microphoneCache)->only($ids);
    }

    public function scopeAudio(Builder $query)
    {
        return $query->where('rooms.type', 'audio');
    }

    public function admins_v2()
    {
        return User::whereIn('id', explode(',', $this->room_admin ?? ''))
            ->get();
    }

    public function getTotalAdminsAttribute(): int
    {
        return (int) $this->max_admin + (int) $this->additional_admin;
    }
}
