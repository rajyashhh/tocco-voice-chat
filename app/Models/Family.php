<?php

namespace App\Models;

use function request;
use App\Traits\Families\ResourceTrait;
use App\Traits\TimestampsWithTimezone;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Family extends Model
{
    use ResourceTrait, TimestampsWithTimezone;

    protected $guarded = [];

    protected $appends = [];

    private $cachedLevelMax = null;

    public function users()
    {
        return $this->hasManyThrough(User::class, FamilyUser::class, 'family_id', 'family_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMembersNumAttribute()
    {
        $fu = FamilyUser::query()->where('family_id', $this->id)->where('status', 1)/* ->where ('user_type',0) */->count();

        return $fu;
    }

    public function members()
    {
        return $this->hasMany(FamilyUser::class, 'family_id')->where('status', 1)->where('user_type', 0);
    }

    public function allMembers()
    {
        return $this->hasMany(FamilyUser::class, 'family_id')->where('status', 1)->where('user_type', '!=', 2);
    }

    public function currentLevel()
    {
        return $this->belongsTo(FamilyLevel::class, 'current_level_id');
    }

    public function getMembersCountAttribute()
    {
        $fu = FamilyUser::query()->where('family_id', $this->id)->where('status', 1)->count();

        return $fu - 1;
    }

    public function getAdminsNumAttribute()
    {
        $fu = FamilyUser::query()->where('family_id', $this->id)->where('status', 1)->where('user_type', 1)->count();

        return $fu;
    }

    public function admins()
    {
        return $this->hasMany(FamilyUser::class, 'family_id')->where('status', 1)->where('user_type', 1);
    }

    public function getLevelAttribute()
    {
        $giftLogs = $this->total_diamond;
        $cur_level = $this->getLevelMax();
        $next = FamilyLevel::query()->where('exp', '>', $giftLogs)->orderBy('exp')->first();
        $next_level = $next ?? $cur_level;
        $min_exp = @$cur_level->exp ?: 0;
        $over = $giftLogs - $min_exp;
        $diff = @$next_level->exp - @$cur_level->exp;

        $lev = [
            'level_exp' => @(int) $cur_level->exp ?: 0,
            'level_name' => @$cur_level->name ?: '',
            'level_img' => @$cur_level->img ?: '',
            'family_exp' => (int) $giftLogs,
            'over_current_level_exp' => (int) $over,
            'next_exp' => @(int) $next_level->exp,
            'next_name' => @$next_level->name,
            'next_img' => @$next_level->img,
            'per' => $diff <= 0 ? 0 : (($over > $diff) ? 1 : (float) ($over / $diff)),
            'rem' => ($over > $diff) ? 0 : (int) ($diff - $over),
            'is_last_level' => (bool) ($next === null),
        ];

        return $lev;
    }
    // public function  getLevelMax()
    // {
    //     $giftLogs = $this->total_diamond;
    //     return FamilyLevel::query()->where('exp', '<=', $giftLogs)->orderByDesc('exp')->first();
    // }

    public function getLevelMax()
    {
        if ($this->cachedLevelMax === null) {
            // Cache all levels once (usually < 20 records)
            static $allLevels = null;
            if ($allLevels === null) {
                $allLevels = \Cache::remember('family_levels_all', 300, fn() => 
                    FamilyLevel::orderByDesc('exp')->get()
                );
            }
            
            $giftLogs = $this->total_diamond ?? 0;
            $this->cachedLevelMax = $allLevels->first(fn($level) => $level->exp <= $giftLogs);
        }

        return $this->cachedLevelMax;
    }

    public function levelMax(): HasOne
    {
        // This is a query builder, not a real foreign key relation
        return $this->hasOne(FamilyLevel::class, 'id', 'id')
            ->where('exp', '<=', $this->total_diamond)
            ->orderByDesc('exp');
    }

    public function getLevelMaxMembersNumAttribute()
    {
        $level = $this->getLevelMax();
        return $level?->members;
    }

    public function getMaxExpAttribute()
    {
        $level = $this->getLevelMax();
        return $level?->exp ?? 0;
    }

    public function getMaxLevelAttribute()
    {
        $level = $this->getLevelMax();
        if (!$level) return '';
        
        return app()->getLocale() === 'ar' 
            ? ($level->name ?? $level->name_en) 
            : ($level->name_en ?? $level->name);
    }



    public function getLevelMaxAdminsNumAttribute()
    {
        $level = $this->getLevelMax();
        return $level?->admins;

        return null;
    }

    public function getNumAttribute()
    {
        if ($this->level_max_members_num) {
            return $this->level_max_members_num;
        }

        return $this->attributes['num'];
    }

    public function getNumAdminsAttribute()
    {
        if ($this->level_max_admins_num) {
            return $this->level_max_admins_num;
        }

        return 2;
    }

    // public function getRankAttribute0(){
    //     $gl = GiftLog::query ()->where (function ($q){
    //         $q->where('sender_family_id',$this->id)->orWhere('receiver_family_id',$this->id);
    //     })->sum('giftPrice');
    //     return $gl;
    // }

    public function getRankAttribute()
    {
        $time = request('time');

        if ($time === 'today') {
            // dd($this->today_rank);

            //            $gl = GiftLog::query ()->whereRaw('CAST(created_at AS DATE) = CAST(NOW() AS DATE)')->where (function ($q) use ($time){
            //                $q->where('sender_family_id',$this->id)->orWhere('receiver_family_id',$this->id);
            //            })->sum('giftPrice');
            $gl = $this->today_rank;
        } elseif ($time === 'week') {
            //            $gl = GiftLog::query ()->whereRaw('WEEK(CAST(created_at AS DATE)) = WEEK(CAST(NOW() AS DATE))')->where (function ($q) use ($time){
            //                $q->where('sender_family_id',$this->id)->orWhere('receiver_family_id',$this->id);
            //            })->sum('giftPrice');
            $gl = $this->week_rank;
        } elseif ($time === 'month') {
            //            $gl = GiftLog::query ()->whereRaw('MONTH(CAST(created_at AS DATE)) = MONTH(CAST(NOW() AS DATE))')->where (function ($q) use ($time){
            //                $q->where('sender_family_id',$this->id)->orWhere('receiver_family_id',$this->id);
            //            })->sum('giftPrice');
            $gl = $this->month_rank;
        } else {

            $gl = $this->month_rank;
        }

        return $gl;
    }

    public function getTodayRankAttribute($val)
    {
        /*$gl = GiftLog::query()->whereRaw('CAST(created_at AS DATE) = CAST(NOW() AS DATE)')->where(function ($q) {
            $q->where('sender_family_id', $this->id)->orWhere('receiver_family_id', $this->id);
        })->sum('giftPrice');
        if ($val !== $gl) {
            $this->attributes['today_rank'] = $gl;
            $this->save();
        }*/

        return $this->attributes['today_rank'] ?? 0;
    }

    public function getWeekRankAttribute($val)
    {
        /*$gl = GiftLog::query()->whereRaw('WEEK(CAST(created_at AS DATE)) = WEEK(CAST(NOW() AS DATE))')->where(function ($q) {
            $q->where('sender_family_id', $this->id)->orWhere('receiver_family_id', $this->id);
        })->sum('giftPrice');
        if ($val !== $gl) {
            $this->attributes['week_rank'] = $gl;
            $this->save();
        }*/

        return $this->attributes['week_rank'] ?? 0;
    }

    public function getMonthRankAttribute($val)
    {
        /*$gl = GiftLog::query()->whereRaw('MONTH(CAST(created_at AS DATE)) = MONTH(CAST(NOW() AS DATE))')->where(function ($q) {
            $q->where('sender_family_id', $this->id)->orWhere('receiver_family_id', $this->id);
        })->sum('giftPrice');
        if ($val !== $gl) {
            $this->attributes['month_rank'] = $gl;
            $this->save();
        }*/

        return $this->attributes['month_rank'] ?? 0;
    }

    /*public function setTodayRankAttribute()
    {
        $gl = GiftLog::query()->whereRaw('CAST(created_at AS DATE) = CAST(NOW() AS DATE)')->where(function ($q) {
            $q->where('sender_family_id', $this->id)->orWhere('receiver_family_id', $this->id);
        })->sum('giftPrice');
        $this->attributes['today_rank'] = $gl;
    }

    public function setWeekRankAttribute()
    {
        $gl = GiftLog::query()->whereRaw('WEEK(CAST(created_at AS DATE)) = WEEK(CAST(NOW() AS DATE))')->where(function ($q) {
            $q->where('sender_family_id', $this->id)->orWhere('receiver_family_id', $this->id);
        })->sum('giftPrice');
        $this->attributes['week_rank'] = $gl;
    }

    public function setMonthRankAttribute()
    {
        $gl = GiftLog::query()->whereRaw('MONTH(CAST(created_at AS DATE)) = MONTH(CAST(NOW() AS DATE))')->where(function ($q) {
            $q->where('sender_family_id', $this->id)->orWhere('receiver_family_id', $this->id);
        })->sum('giftPrice');
        $this->attributes['month_rank'] = $gl;
    }*/

    public function getRankStringAttribute()
    {
        return numToString($this->rank);
    }
}
