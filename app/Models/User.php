<?php

namespace App\Models;

use DB;
use App\Helpers\Common;
use App\Traits\FollowTrait;
use Modules\CP\Entities\Cp;
use Modules\Vip\Entities\Vip;
use App\Traits\User\UserLevel;
use Modules\Vip\Entities\OVip;
use App\Helpers\UserPackHelper;
use Modules\Reals\Entities\Real;
use Laravel\Sanctum\HasApiTokens;
use Modules\Badge\Entities\Badge;
use Modules\Vip\Entities\UserVip;
use App\Traits\PaymentGetWayTrait;
use Modules\Moment\Entities\Moment;
use Illuminate\Support\Facades\Auth;
use App\Models\Config as ConfigModel;
use Modules\Badge\Entities\UserBadge;
use App\Traits\TimestampsWithTimezone;
use Illuminate\Support\Facades\Config;
use Modules\Chat\Traits\ChatUserTrait;
use App\Traits\MomentRelationshipTrait;
use Illuminate\Support\Facades\Storage;
use Modules\SpecialId\Traits\SpecialId;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Moment\Entities\MomentUserGift;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\AgencyApp\Entities\AdditionalInfo;
use Modules\HostLevel\Entities\HostLevelWinner;
use Modules\Reals\Traits\RealRelationshipTrait;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Modules\Achievement\Http\Traits\AchievementUser;
use Modules\SalaryTransaction\Entities\ChargeAgency;
use Modules\SalaryTransaction\Entities\SalaryRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\SalaryTransaction\Traits\UserTransferTrait;
use Modules\UsersWallet\Entities\UserWallet;

/**
 * @method static withoutAppends()
 */
class User extends Authenticatable
{
    use AchievementUser, ChatUserTrait, FollowTrait, HasApiTokens, HasFactory, MomentRelationshipTrait, Notifiable, PaymentGetWayTrait, RealRelationshipTrait, SoftDeletes, SpecialId, TimestampsWithTimezone, UserTransferTrait, UserLevel;

    /*
     * To enable and disable observer saving and updating methods
     */
    public static $withoutAppends = false;

    public $enableSaving = true;

    protected $loadedPacks = null;

    protected $specialPack = null;

    const TYPE_REGULAR = 0;
    const TYPE_HOST = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',

    ];

    protected $dates = ['deleted_at'];

    protected ?string $cachedComputedUuid = null;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'salary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'di' => 'integer',
        'received_level' => 'integer',
        'sender_level' => 'integer',
        'charge_status' => 'boolean',
        'transfer_salary' => 'boolean',
        'salary_is_updated' => 'boolean',
    ];

    protected $appends = [
        //        'user_diamond',
        'total_sender_level',
        'total_received_level',
        'original_uuid',
        //        'is_frozen',
        //        'total_charge_level',
        //        'photo',
        //        'org_online_time',
        //        'user_types',
        //        'vip_data',
        //        'level_data',
        //        'profile_frame',
        //        'profile_frame_id'

    ];



    /* protected $appends = [
         'my_store',
         'lang',
         'avatar',
         'gender',
         'flag',
         'usd',
         'is_family_admin',
         'is_family_owner',
         'intro',
         'frame',

     ];*/

    public function images()
    {
        return $this->hasMany(ProfileGallary::class);
    }

    public function mutualFollows()
    {
        return $this->belongsToMany(self::class, 'follows', 'user_id', 'followed_user_id')
            ->withPivot('status', 'created_at')
            ->wherePivot('status', 1)
            ->whereIn('followed_user_id', function ($query) {
                $query->select('user_id')
                    ->from('follows')
                    ->whereColumn('follows.user_id', 'follows.followed_user_id')
                    ->where('follows.status', 1);
            });
    }

    public function ignores()
    {
        return $this->belongsToMany(self::class, 'profile_user_ignores', 'user_id', 'ignore_user_id')
            ->withTimestamps();
    }

    public function ignoredBy()
    {
        return $this->belongsToMany(self::class, 'profile_user_ignores', 'ignore_user_id', 'user_id')
            ->withTimestamps();
    }

    public function sameDeviceUsers()
    {
        return $this->hasMany(self::class, 'device_token', 'device_token');
    }
    public function lovelyRelations()
    {
        return Cp::where(function ($q) {
            $q->where('user_one_id', $this->id)
                ->orWhere('user_two_id', $this->id);
        })
            ->whereIn('status', [1, 4])
            ->whereHas('relation', function ($q) {
                $q->where('type', 'lovely');
            });
    }
    public function likes()
    {
        return $this->belongsToMany(self::class, 'profile_user_likes', 'user_id', 'liked_user_id')
            ->withTimestamps();
    }

    public function likedBy()
    {
        return $this->belongsToMany(self::class, 'profile_user_likes', 'liked_user_id', 'user_id')
            ->withTimestamps();
    }

    public function cpsAsOne()
    {
        return $this->hasMany(Cp::class, 'user_one_id');
    }

    public function userBadges()
    {
        return $this->hasMany(Badge::class, 'user_id')->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        });
    }

    public function latestTarget()
    {
        return $this->hasOne(UserTarget::class)->latestOfMany();
    }

    public function gamePercentage()
    {
        return $this->hasOne(PercentageGameUsers::class);
    }

    public function cpsAsTwo()
    {
        return $this->hasMany(Cp::class, 'user_two_id');
    }

    public function allCps()
    {
        return $this->cpsAsOne()->union($this->cpsAsTwo());
    }
    public function agencyUserJob()
    {
        return $this->hasOne(AgencyUserJob::class, 'user_id', 'id');
    }

    public function agencyJoinRequest()
    {
        return $this->hasMany(AgencyJoinRequest::class, 'user_id');
    }

    public function userAgencyJoined()
    {
        return $this->hasMany(UsersJoinedAgency::class, 'user_id');
    }

    public function timeLog()
    {
        return $this->hasMany(TimeLog::class, 'user_id');
    }

    public function vipImage()
    {
        return $this->hasOne(Vip::class, 'level', 'total_received_level')
            ->where('type', 1);
    }

    public function monthlyDiamondReceive()
    {
        return $this->hasMany(MonthlyDiamondReceive::class, 'user_id', 'id');
    }
    public function currentMonthlyDiamond()
    {
        return $this->hasOne(MonthlyDiamondReceive::class, 'user_id', 'id')
            ->where('month', now()->month)
            ->where('year', now()->year);
    }

    public function getMonthlyDiamondReceivedAttribute()
    {
        return $this->getMonthlyDiamondReceived();
    }

    // method for passing params
    public function getMonthlyDiamondReceived($month = null, $year = null)
    {
        $month = $month ?? now(getTimezone())->month;
        $year  = $year ?? now(getTimezone())->year;

        return $this->monthlyDiamondReceive()
            ->where('month', $month)
            ->where('year', $year)
            ->sum('monthly_diamond_received') ?? 0;
    }

    public function setMonthlyDiamondReceivedAttribute($value)
    {
        $date = \Carbon\Carbon::now(getTimezone());

        $this->monthlyDiamondReceive()->updateOrCreate(
            [
                'month' => $date->month,
                'year'  => $date->year,
            ],
            []
        )->increment('monthly_diamond_received', $value);

        
    }


    public function getUserTypeAttribute()
    {
        return match ((int) ($this->type_user)) {
            0 => 'user',
            1 => 'host',
            2 => 'Host agent',
            3 => 'freight forwarder',
            4 => 'freight forwarder and Host agent',
            5 => 'Administrative',
            default => 'user',
        };
    }

    public function getFamilyIdAttribute($value)
    {
        return $value === 0 ? null : $value;
    }

    public function getTotalDays()
    {
        $month = @request()->month;
        $year = @request()->year;
        if (! $month) {
            $month = now()->month;
        }
        if (! $year) {
            $year = now()->year;
        }
        $hours_days = \Cache::get('hours_days') ?? 2;
        $subQuery = DB::table('live_times')
            ->select('uid', DB::raw('COUNT(*) AS entry_count'))
            ->whereMonth('created_at', $month)->whereYear('created_at', $year)
            ->where('uid', $this->id)
            ->groupBy('uid', DB::raw('DATE(created_at)')) // Group by uid and date
            ->havingRaw('SUM(hours) >= ?', [$hours_days])
            ->get();

        return $subQuery->count('entry_count');
    }

    public function getTotalDaysJoinedAgency($from_date = null)
    {
        $month = @request()->month;
        $year = @request()->year;
        if (! $month) {
            $month = now()->month;
        }
        if (! $year) {
            $year = now()->year;
        }

        $query = $this->liveTime()
            ->selectRaw('DATE(created_at) as date, SUM(hours) as total_hours')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year);

        if ($from_date) {
            $query->where('created_at', '>=', $from_date);
        }
        $hours_days = \Cache::get('hours_days') ?? 2;

        $days = $query
            ->groupBy('date')
            ->having('total_hours', '>=', $hours_days)
            ->get();

        return $days->count();
    }

    public function getTotalDaysJoinedAgencyByMonth($startDate, $endDate, $year)
    {
        $month = \Carbon\Carbon::parse($startDate)->month;

        if (!$year) {
            $year = \Carbon\Carbon::parse($startDate)->year;
        }

        $query = $this->liveTime()
            ->selectRaw('DATE(created_at) as date, SUM(hours) as total_hours')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);

        $hours_days = \Cache::get('hours_days') ?? 2;

        $days = $query
            ->groupBy('date')
            ->having('total_hours', '>=', $hours_days)
            ->get();

        return $days->count();
    }

    public function getSallaryInfo(): array
    {
        $month = (int) @request()->month;
        $year = (int) @request()->year;
        if (! $month) {
            $month = now()->month;
        }
        if (! $year) {
            $year = now()->year;
        }

        $userSallary = UserSallary::query()
            ->selectRaw('sum(sallary) as total_salary, sum(cut_amount) as total_cut_amount')
            ->where(function ($query) use ($year, $month) {
                $query->whereRaw('(year < ? OR (year = ? AND month <= ?))', [$year, $year, $month]);
            })
            ->where('user_id', $this->id)
            ->first();

        return $userSallary?->toArray() ?? [];
    }

    public function getSallaryInfoByMonth(): array
    {
        $month = now()->month;
        $year = now()->year;

        $userSallary = UserSallary::query()
            ->selectRaw('sum(sallary) as total_salary, sum(cut_amount) as total_cut_amount')
            ->where('user_id', $this->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return $userSallary?->toArray() ?? [];
    }

    public function getSallaryInfoByMonth2($month, $year, $agencyId): array
    {
        $userSallary = UserSallary::query()
            ->selectRaw('sum(sallary) as total_salary, sum(cut_amount) as total_cut_amount')
            ->where('user_id', $this->id)
            ->where('is_finished', 0)
            ->where('user_agency_id', '=', $agencyId)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return $userSallary?->toArray() ?? [];
    }

    public function additionalInfo()
    {
        return $this->hasMany(AdditionalInfo::class, 'user_id');
    }

    public function mangerType()
    {
        return $this->belongsTo(MangerType::class, 'manger_type_id');
    }

    public function manager()
    {
        return $this->hasOne(Admin::class, 'app_id');
    }

    public function requestBackgroundImages()
    {
        return $this->hasMany(RequestBackgroundImage::class, 'owner_room_id');
    }

    public function giftLogsSender()
    {
        return $this->hasMany(GiftLog::class, 'sender_id');
    }

    public function luckyGifts()
    {
        return $this->hasMany(UserLuckyGift::class);
    }

    public function follows()
    {
        return $this->hasMany(Follow::class, 'user_id');
    }

    public function coinGameUser()
    {
        return $this->hasMany(CoinGameUser::class);
    }

    public function exchangeLogs()
    {
        return $this->hasMany(ExchangeLog::class);
    }

    // public function coinLogs()
    // {
    //     return $this->hasMany(CoinLog::class);
    // }

    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    public function chat_settings()
    {
        return $this->hasMany(ChatSetting::class);
    }

    public function userSetting()
    {
        return $this->hasOne(UserSetting::class, 'user_id');
    }

    public function codeInvitations()
    {
        return $this->belongsToMany(self::class, 'user_code_invitations', 'user_id', 'id')->withPivot('updated_at');
    }

    public function codeInvitationsEarn()
    {
        return $this->hasMany(UserEarnInvitation::class, 'parent_id');
    }

    public function userCodeInvite()
    {
        return $this->hasMany(UserCodeInvitation::class, 'user_id');
    }


    public function scopeWithoutAppends($query)
    {
        self::$withoutAppends = true;

        return $query;
    }

    public function dress1()
    {
        return $this->hasOne(Ware::class, 'id', 'dress_1')->where('wares.type', 4)->where('wares.enable', 1)->select('id', 'img1', 'img2', 'image_type');
    }

    public function dress2()
    {
        return $this->hasOne(Ware::class, 'id', 'dress_2')->where('wares.type', 5)->where('wares.enable', 1)->select('id', 'img1', 'img2', 'show_img', 'image_type');
    }

    public function dress3()
    {
        return $this->hasOne(Ware::class, 'id', 'dress_3')->where('wares.type', 6)->where('wares.enable', 1)->select('id', 'img1', 'img2', 'image_type');
    }

    public function is_in_live()
    {
        // Check if user has any active room with visitors
        return $this->rooms()->where('room_status', 1)->whereHas('roomVisitors')->exists();
    }

    public function ips()
    {
        return $this->hasMany(Ip::class, 'uid');
    }

    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    public function getMyStoreAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return Common::my_store($this->attributes['id']);
    }

    public function getAvatarAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        // Photoless users must return empty (NOT a default logo/icon path): the app
        // shows the user's name initials (InitialsAvatar) as the only avatar fallback.
        // Admin panels keep their own businessman-icon default via explicit `?: $defaultImage`.
        return @$this->profile()->first()->avatar ?: '';
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id');
    }

    public function getGenderAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return @$this->profile->gender ?? 1;
    }

    public function getFlagAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return @$this->country()->first()->flag ?: '';
    }

    public function country()
    {
        return $this->belongsTo(Country::class)->select('id', 'name', 'flag', 'language', 'e_name', 'phone_code', 'iso', 'iso_numeric', 'currency_numeric');
    }

    public function getLangAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return @$this->country()->language ?: 'en';
    }

    public function getNicknameAttribute($val)
    {
        return $val ?: '';
    }

    public function profileVisits()
    {
        return $this->belongsToMany(self::class, 'profile_visitors', 'user_id', 'visitor_id', 'id', 'id')->orderByDesc('profile_visitors.updated_at')->withPivot(['updated_at', 'created_at']);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class, 'uid');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function agencies()
    {
        return $this->hasMany(Agency::class, 'agency_manger_id');
    }

    public function liveTime()
    {
        return $this->hasMany(LiveTime::class, 'uid');
    }

    public function getLiveTimeThisMonth()
    {
        $fromDate = now()->startOfMonth()->toDateString();

        $period = Common::getEffectiveJoinPeriod($this->id, $this->agency_id, $fromDate);

        return LiveTime::where('uid', $this->id)
            ->whereBetween('created_at', [$period['start_date'], $period['end_date']])
            ->sum('hours');
    }


    public function UserliveTime()
    {
        return $this->hasMany(LiveTime::class);
    }

    public function scopeOfAgency($q)
    {
        $user = Auth::user();
        if (Auth::user()->isRole('agency')) {
            $q->whereNotNull('agency_id')->where('agency_id', '=', @$user->agency_id);
        }
    }

    public function getUsdAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return $this->old_usd + $this->target_usd - $this->target_token_usd;
    }

    //    protected static function booted()
    //    {
    //        if (Auth::user ()->isRole('agency')){
    //            static::addGlobalScope('of_agency', function (Builder $builder){
    //                $builder->where('agency_id', '=', Auth::id ());
    //            });
    //        }
    //
    //    }

    // Dashboard Relations
    public function user_family()
    {
        return $this->hasOne(Family::class, 'user_id');
    }

    public function getIsFamilyAdminAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }
        $family_user = FamilyUser::query()->where('user_id', $this->id)->where('status', 1)->first();
        if ($family_user) {
            if ($family_user->user_type === 1) {
                return true;
            }

            return false;
        }

        return false;
    }

    public function getIsFamilyOwnerAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }
        $family = Family::query()->where('user_id', $this->id)->exists();
        if ($family) {
            return true;
        }

        return false;
    }

    public function getImgAttribute()
    {
        return $this->avatar;
    }

    public function targets()
    {
        return $this->hasMany(UserTarget::class);
    }

    public function bans()
    {
        return $this->hasMany(Ban::class, 'uid', 'uuid');
    }

    public function salaryRequests(): HasMany
    {
        return $this->hasMany(SalaryRequest::class, 'host_id', 'id');
    }

    public function getFollowDate($id)
    {
        $f =
            Follow::query()->where('user_id', $this->id)->where('followed_user_id', @request()->user()->id)->value('created_at');
        if ($f) {
            return $f;
        }

        return '';
    }

    public function getFollowDateAttribute()
    {
        $f =
            Follow::query()->where('user_id', $this->id)->where('followed_user_id', @request()->user()->id)->value('created_at');
        if ($f) {
            return $f;
        }

        return '';
    }

    public function getFollowedDateAttribute()
    {
        $f =
            Follow::query()->where('user_id', @request()->user()->id)->where('followed_user_id', $this->id)->value('created_at');
        if ($f) {
            return $f;
        }

        return '';
    }

    public function getIntroAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return Common::getUserDress($this->id, $this->dress_3, 6, 'show_img', true);
    }

    public function getFrameAttribute()
    {
        if (self::$withoutAppends) {
            return;
        }

        return Common::getUserDress($this->id, $this->dress_1, 4, 'show_img', true);
    }

    public function getBubbleAttribute()
    {
        return Common::getUserDress($this->id, $this->dress_2, 5, 'show_img', true);
    }

    public function intros_count()
    {
        return Pack::query()->where('user_id', $this->id)->where('type', 6)->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->count();
    }

    public function frames_count()
    {
        return Pack::query()->where('user_id', $this->id)->where('type', 4)->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->count();
    }

    public function bubble_count()
    {
        return Pack::query()->where('user_id', $this->id)->where('type', 5)->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->count();
    }

    public function hasRoom()
    {
        return Room::query()->where('uid', $this->id)->exists();
    }

    public function ownerRoom()
    {
        return $this->hasOne(Room::class, 'uid', 'id');
    }

    public function ownerAudioRoom()
    {
        return $this->hasOne(Room::class, 'uid', 'id')->where('type', 'audio');
    }

    public function ownerLiveRoom()
    {
        return $this->hasOne(Room::class, 'uid', 'id')->where('type', 'live');
    }

    public function familyType()
    {
        return $this->hasOne(FamilyUser::class, 'user_id', 'id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function getIsAgentAttribute()
    {
        return $this->ownAgency()->exists();
    }

    public function ownAgency()
    {
        return $this->hasOne(Agency::class, 'app_owner_id', 'id');
    }

    public function UserVip()
    {
        return $this->hasOne(UserVip::class, 'user_id')->where(function ($q) {
            $q->where('is_used', 1)->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp));
        })->with('OVip')->orderByDesc('level');
    }

    public function userHaveVip()
    {
        return $this->hasMany(UserVip::class, 'user_id')->where(function ($q) {
            $q->where('is_used', 1)->where(fn($q) => $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp));
        })->with('OVip')->orderByDesc('level');
    }

    public function Ovip()
    {
        return $this->hasOneThrough(OVip::class, UserVip::class, 'user_id', 'id', 'id', 'vip_id')
            ->with('privilegs');
    }

    public function haveVip()
    {
        return $this->hasMany(UserVip::class, 'user_id');
    }

    public function getImageReceiverOrSender($name, $type)
    {
        $amount =
            $type === 2 ? $this->sender_level + $this->sub_sender_level : $this->received_level + $this->sub_receiver_level;
        $level = Vip::collectionBuilder()->where('type', $type)->where('level', $amount)->orderByDesc('exp')->first();

        return $level;
    }

    public function followers()
    {
        return $this->hasMany(Follow::class, 'followed_user_id', 'id');
    }

    public function followeds()
    {
        return $this->hasMany(Follow::class, 'user_id', 'id');
    }

    public function isFollowedBy($userId): bool
    {
        return $this->followers()
            ->where('user_id', $userId)
            ->where('status', 1)
            ->exists();
    }

    public function followBack(self $user)
    {
        $userId = $user->id;

        return $this->followers()->where('user_id', $userId)->exists();
    }

    public function followersMoment()
    {
        return $this->belongsToMany(self::class, 'follows', 'followed_user_id', 'user_id');
    }

    public function canJoinRoom(int $roomId): bool
    {
        return $this->id === $roomId;
    }

    public function getSalaryAttribute()
    {
        // $userSalary = UserSallary::query()

        //     ->where('user_id', $this->id)
        //     ->orderByDesc('id')
        //     ->sum(DB::raw('sallary - cut_amount'));
        $roomSalary = 0;
        $userSalary = $this->relationLoaded('totalUserSalary')
            ? $this->totalUserSalary->sum(fn($item) => $item->sallary - $item->cut_amount)
            : $this->totalUserSalary()->sum(DB::raw('sallary - cut_amount'));

        // $roomSalary = RoomSalary::query()->whereHas('room', function ($q) {
        //     $q->where('uid', $this->id);
        // })
        //     ->orderByDesc('id')
        //     ->sum(DB::raw('salary - cut_amount'));

        $total = $userSalary + $roomSalary;
        // $total = wallet_available_by_user($this->id);
        return floor($total * 100) / 100;
    }

    public function getSalaryByAgencyAttribute()
    {
        $userSalary = UserSallary::query()

            ->where('user_id', $this->id)
            ->where('user_agency_id', $this->agency_id)
            ->orderByDesc('id')
            ->sum(DB::raw('sallary - cut_amount'));
        return floor($userSalary * 100) / 100;
    }



    public function getSalaryWithoutCutAmountAttribute()
    {
        $userSallary = UserSallary::query()
            ->where('user_id', $this->id)
            ->sum(DB::raw('sallary'));

        return round($userSallary, 2);
    }

    public function getSalaryWithoutCutAmountAttributeByAgency()
    {
        $userSallary = UserSallary::query()
            ->where('user_id', $this->id)
            ->where('user_agency_id', $this->agency_id)
            ->sum(DB::raw('sallary'));

        return round($userSallary, 2);
    }

    public function setTotalChargeLevelAttribute(float $value)
    {
        $level = @$this->charge_level + $this->sub_charger_level;
        if ($level === $value) {
            return;
        }

        $this->sub_charger_level = $value - @$this->charge_level ?? 0;
        $diamonds = (@Vip::query()->where('type', 5)->where('level', '=', $value)->orderByDesc('exp')->limit(1)->first())?->exp ?? 0;
        $this->sub_charger_coins = $diamonds - $this->total_charge_coins;
    }

    public function getTotalChargeLevel(float $value)
    {
        $diamonds = (@Vip::query()->where('type', 5)->where('level', '=', $value)->orderByDesc('exp')->limit(1)->first()) ?? 0;

        return $diamonds;
    }

    public function getTotalChargeLevelAttribute()
    {
        return $this->charge_level + $this->sub_charger_level;
    }

    public function setSalaryAttribute()
    {
        if ($this->agency_id) {
            $salary =
                UserSallary::query()->where('user_id', $this->id)->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));
            $this->attributes['salary'] = $salary;
        } else {
            $this->attributes['salary'] = 0;
        }
    }

    public function getNowRoomUidAttribute($value)
    {
        return $value === 0 ? null : $value;
    }

    public function room()
    {
        return $this->hasOne(Room::class, 'id', 'now_room_uid');
    }

    public function nowRoom()
    {
        return $this->hasOne(Room::class, 'id', 'now_room_uid');
    }

    public function nowAudioRoom()
    {
        return $this->hasOne(Room::class, 'id', 'now_room_uid')->where('type', 'audio');
    }
    public function myroom()
    {
        return $this->hasOne(Room::class, 'uid', 'id');
    }

    public function color_image()
    {
        return $this->hasOne(ImageColor::class, 'id', 'image_color_id');
    }

    public function getOldAttribute()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $old =
            UserSallary::query()->where('user_id', $this->id)->whereRaw("CONCAT(year, LPAD(month, 2, '0')) != CONCAT('$currentYear', LPAD('$currentMonth', 2, '0'))")->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));

        return $old;
    }

    public function setOldUsdAttribute()
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $old =
            UserSallary::query()->where('user_id', $this->id)->whereRaw("CONCAT(year, LPAD(month, 2, '0')) != CONCAT('$currentYear', LPAD('$currentMonth', 2, '0'))")->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));
        $this->attributes['old_usd'] = $old;
    }

    public function target($month = null, $year = null)
    {
        if (! $month) {
            $month = date('m');
        }
        if (! $year) {
            $year = date('Y');
        }

        return $this->hasMany(UserSallary::class)->where('month', $month)->where('year', $year)->first();
    }

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }


    public function followPacks()
    {
        return $this->hasMany(Pack::class, 'user_id', 'id')->whereIn('packs.type', [4, 18])->where(function ($q) {
            $q->where('packs.expire', 0)->orWhere('packs.expire', '>=', time());
        });
    }

    public function ware()
    {
        return $this->hasOne(Ware::class, 'id', 'dress_1')->where('wares.type', 4)->where('wares.enable', 1)->select('id', 'img1', 'img2');
    }

    public function getCoinsStringAttribute()
    {
        return numToString($this->di);
    }

    public function storeLastMonthlyDiamondReceivedInHistory()
    {
        $lastDiamondReceived = $this->monthly_diamond_received;

        // Get the last history record
        $lastHistory = $this->history()->latest()->first();

        // Compare with the last history record to avoid duplications
        if (! $lastHistory || $lastDiamondReceived !== $lastHistory->diamond) {
            $this->history()->create([
                'user_id' => $this->id,
                'agency_id' => $this->agency_id,
                'diamond' => $lastDiamondReceived,
                'month' => now()->month,
                'year' => now()->year,
                'pid' => $this->id,
            ]);
        }
    }

    public function history()
    {
        return $this->hasMany(History::class);
    }

    public function storeMonthlyDiamondReceivedInHistory()
    {
        $this->history()->create([
            'diamond' => $this->monthly_diamond_received,
        ]);
    }

    public function userSallary()
    {
        return $this->hasOne(UserSallary::class, 'user_id', 'id');
    }

    public function totalUserSalary()
    {
        return $this->hasMany(UserSallary::class, 'user_id', 'id');
    }

    public function userPacks()
    {
        return $this->hasMany(Pack::class, 'user_id');
    }

    public function packs()
    {
        return $this->hasMany(Pack::class)->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        });
    }

    public function packsUser()
    {
        return $this->hasMany(Pack::class, 'user_id')->whereIn('type', [4, 5, 6, 25])->where('get_type', '!=', 1)->where('is_used', 1)->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        });
    }
    // public function hasPackOfType(int $type, bool $onlyUsed = false): bool
    // {
    //     return $this->packs()
    //         ->where('type', $type)
    //         ->when($onlyUsed, fn($q) => $q->where('is_used', 1))
    //         ->exists();
    // }

    public function hasPackOfType(int $type, bool $onlyUsed = false): bool
    {
        // if packs relation already loaded, filter in-memory
        if ($this->relationLoaded('packs')) {
            return $this->packs
                ->where('type', $type)
                ->when($onlyUsed, fn($q) => $q->where('is_used', 1))
                ->isNotEmpty();
        }

        // fallback: query database
        return $this->packs()
            ->when($onlyUsed, fn($q) => $q->where('is_used', 1))
            ->where('type', $type)
            ->exists();
    }

    public function sendPacks()
    {
        return $this->hasMany(Pack::class, 'sender_id');
    }

    public function scopeIsFollow($query, $userId)
    {
        return $query->withExists('followeds', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        });
    }

    public function scopeGetFollowers(Builder $query, $userId): Builder
    {
        return $query->whereHas('followers', function ($query) use ($userId) {
            $query->where('user_id', $userId)->where('status', 1);
        });
    }

    public function getTotalSenderLevelAttribute()
    {
        return $this->sender_level;
    }

    public function agencyAdmins()
    {
        return $this->hasOne(AgencyUserJob::class, 'user_id')->where('type', 'requestManger');
    }

    public function getTotalSenderDiamondsAttribute()
    {
        return $this->total_diamond_send + $this->sub_sender_num;
    }

    public function getTotalReceivedDiamondsAttribute()
    {
        return $this->total_diamond_received + $this->sub_receiver_num;
    }

    public function getTotalReceivedLevelAttribute()
    {
        return $this->received_level;
    }

    public function setTotalSenderLevelAttribute(float $value)
    {
        $level = $this->sender_level;
        if ($level === $value) {
            return;
        }
        $expPercentages = Config::get('exp_percentages') ?? [0, 0];

        $this->sub_sender_level = 0;
        $this->sender_level = $value;
        $diamonds = (@Vip::query()->where('type', 2)->where('level', '=', $value)->orderByDesc('exp')->limit(1)->first())?->exp ?? 0;
        //        $this->sub_sender_num   = $diamonds - $this->total_diamond_send;
        //        $this->sub_sender_num   = ($diamonds - $this->total_diamond_send) + (( (( $diamonds - $this->total_diamond_send) * ( 2 * ($expPercentages[0] / 100)))) );
        /*if ($expPercentages[0] <= 1) {
            $this->sub_sender_num = ($diamonds - $expPercentages[0] * $this->total_diamond_send) / $expPercentages[0];
        } else {*/

        $this->sub_sender_num = ceil(($diamonds / $expPercentages['exp_sender_percentage'])) - $this->total_diamond_send;

        //        }
    }

    public function setTotalReceivedLevelAttribute(float $value)
    {
        $level = $this->received_level;
        if ($level === $value) {
            return;
        }
        $expPercentages = Config::get('exp_percentages') ?? [0, 0];

        $this->sub_receiver_level = 0;
        $this->received_level = $value;
        $diamonds = (@Vip::query()->where('type', 1)->where('level', '=', $value)->orderByDesc('exp')->limit(1)->first())?->exp ?? 0;
        //        $this->sub_receiver_num = $diamonds - $this->total_diamond_received;
        /*if ($expPercentages[0] <= 1) {
            $this->sub_receiver_num = ($diamonds - $this->total_diamond_received * $expPercentages[1]) / $expPercentages[1];
        } else {*/
        $this->sub_receiver_num = (ceil(($diamonds / $expPercentages['exp_received_percentage'])) - $this->total_diamond_received);

        //        }
    }

    public function managedAgencies()
    {
        return $this->hasMany(Agency::class, 'agency_dash_manger_id');
    }

    public function reals()
    {
        return $this->hasMany(Real::class, 'user_id');
    }

    public function moments()
    {
        return $this->hasMany(Moment::class, 'user_id');
    }

    public function admenUsersAPP()
    {
        return $this->hanMany(AdminUser::class);
    }

    public function latestUserSallary()
    {
        // Laravel 8+ supports latestOfMany
        return $this->hasOne(UserSallary::class)->latestOfMany();
    }

    public function getUserDiamondAttribute()
    {
        if ($this->type_user === 0 || $this->type_user === 3) {
            return $this->total_diamond_received;
        }

        return $this->monthly_diamond_received;
    }

    public function setUserDiamondAttribute($value)
    {
        if ($this->type_user === 0) {
            $this->total_diamond_received = $value;

            return;
        }
        $this->monthly_diamond_received = $value;
        $diff =
            $this->monthly_diamond_received - $this->getOriginal('monthly_diamond_received');
        $this->total_diamond_received += $diff;
        if ($this->total_diamond_received < 0) {
            $this->total_diamond_received = 0;
        }
    }

    public function getTotalSallary($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }
        if ($year === null) {
            $year = now()->year;
        }
        if ($this->agency_id) {
            $userSallary = UserSallary::query()->where(function ($query) use ($year, $month) {
                $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
            })->where('user_id', $this->id)
                ->where('is_paid', 0)
                ->where('user_agency_id', $this->agency_id)
                ->orderByDesc('id')
                ->sum(DB::raw('sallary'));

            return floor($userSallary ?? 0);
        }

        return 0;
    }

    public function getTotalDiamond($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }
        if ($year === null) {
            $year = now()->year;
        }

        if ($this->agency_id) {
            $userSallary = UserTarget::query()
                ->where(function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(add_year,"-", add_month)'), '=', $year . '-' . $month);
                })->where('user_id', $this->id)
                ->where('agency_id', $this->agency_id)
                ->orderByDesc('id')
                ->sum(DB::raw('user_diamonds'));
            return floor($userSallary ?? 0);
        }

        return 0;
    }

    public function getTotalCutAmount($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }
        if ($year === null) {
            $year = now()->year;
        }
        if ($this->agency_id) {
            $userSallary = UserSallary::query()->where(function ($query) use ($year, $month) {
                $query->where(DB::raw('concat(year,"-", month)'), '<=', $year . '-' . $month);
            })->where('user_id', $this->id)
                ->where('is_paid', 0)
                ->where('user_agency_id', $this->agency_id)
                ->orderByDesc('id')
                ->sum(DB::raw('cut_amount'));

            return floor($userSallary ?? 0);
        }

        return 0;
    }

    public function sumCutAmount($month = null, $year = null)
    {

        if ($this->agency_id) {
            $userSallary = UserSallary::query()->when(isset($month) && isset($year), function ($query) use ($year, $month) {
                $query->where(function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month);
                });
            })->where('user_id', $this->id)
                ->where('is_paid', 0)
                ->orderByDesc('id')
                ->sum(DB::raw('cut_amount'));

            return truncateAndTrim($userSallary ?? 0);
        }

        return 0;
    }

    public function sumSalary($month = null, $year = null)
    {

        if ($this->agency_id) {
            $userSallary = UserSallary::query()->when(isset($month) && isset($year), function ($query) use ($year, $month) {
                $query->where(function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month);
                });
            })->where('user_id', $this->id)
                ->where('is_paid', 0)
                ->orderByDesc('id')
                ->sum(DB::raw('sallary'));

            return truncateAndTrim($userSallary ?? 0);
        }

        return 0;
    }

    public function sumNetSalary($month = null, $year = null)
    {

        if ($this->agency_id) {
            $userSallary = UserSallary::query()->when(isset($month) && isset($year), function ($query) use ($year, $month) {
                $query->where(function ($query) use ($year, $month) {
                    $query->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month);
                });
            })->where('user_id', $this->id)
                ->where('is_paid', 0)
                ->orderByDesc('id')
                ->sum(DB::raw('sallary - cut_amount'));

            return truncateAndTrim($userSallary);
        }

        return 0;
    }

    public function getOld($month = null, $year = null)
    {
        $currentYear = date('Y');
        $currentMonth = date('m');
        $old =
            UserSallary::query()->when(isset($month), function ($query) use ($month) {
                $query->where('month', '<=', $month);
            })->when(isset($year), function ($query) use ($year) {
                $query->where('year', '<=', $year);
            })->where('user_id', $this->id)->whereRaw("CONCAT(year, LPAD(month, 2, '0')) != CONCAT('$currentYear', LPAD('$currentMonth', 2, '0'))")->where('is_paid', 0)->sum(DB::raw('sallary - cut_amount'));

        return $old;
    }

    public function getSalary($month = null, $year = null)
    {
        if ($month === null) {
            $month = now()->month;
        }
        if ($year === null) {
            $year = now()->year;
        }
        if ($this->agency_id) {
            $userSallary = UserSallary::query()->where(function ($query) use ($year, $month) {
                $query->where(DB::raw('concat(year,"-", month)'), '=', $year . '-' . $month);
            })
                ->where('user_id', $this->id)
                ->where('is_paid', 0)
                //                ->where('user_agency_id', $this->agency_id)
                ->orderByDesc('id')
                ->sum(DB::raw('sallary - cut_amount'));

            return round($userSallary, 2) ?? 0;
        }

        return 0;
    }

    public function carousels(): HasMany
    {
        return $this->hasMany(HomeCarousel::class, 'owner_id');
    }

    public function getLoadedPacks()
    {
        if ($this->loadedPacks === null) {
            //            $this->loadedPacks = $this->packs()->whereIn('type', [20, 18, 17, 20, 19, 16, 13, 3, 4, 5, 25, 6, 12])->where('is_used', 1)->with('ware')->get();
            $this->loadedPacks = $this->packs()->where('is_used', 1)->with('ware')->get();
        }

        return $this->loadedPacks;
    }

    public function eligiblePacks(): HasMany
    {
        return $this->hasMany(Pack::class)
            ->where('is_used', 1)
            ->where(function ($q) {
                $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
            })
            ->with('ware');
    }

    public function getUuidV2Attribute()
    {
        $pack = $this->eligiblePacks
            ->where('ware.value', $this->special_id)
            ->first();

        return ($this->special_id && $pack && $pack->is_used === 1) ? $this->special_id : $this->original_uuid;
    }

    public function getUuidV3Attribute()
    {
        $pack = $this->eligiblePacks->where('type', 25)
            ->where('ware.value', $this->special_id)
            ->first();
        return ($this->special_id && $pack && $pack->is_used === 1) ? $this->special_id : '';
    }

    /**
     * Custom accessor for UUID with special pack conditions.
     */
    // public function getUuidAttribute($value)
    // {
    //     if ($this->relationLoaded('packs')) {

    //         $pack = $this->packs
    //             ->where('type', 25)
    //             ->where('is_used', true)
    //             ->where('ware.value', $this->special_id)
    //             ->first();
    //     } else {

    //         $pack = $this->packs()
    //             ->with('ware')
    //             ->where('type', 25)
    //             ->where('is_used', true)
    //             ->whereHas('ware', fn($q) => $q->where('value', $this->special_id))
    //             ->first();
    //     }

    //     return ($this->special_id && $pack && $pack->is_used === 1)
    //         ? $this->special_id
    //         : $this->original_uuid;
    // }


    public function getUuidAttribute($value)
    {
        if (!$this->relationLoaded('packs')) {
            return $this->original_uuid;
        }

        $pack = $this->packs->first(
            fn($pack) =>
            $pack->type === 25 &&
                $pack->is_used &&
                optional($pack->ware)->value == $this->special_id
        );

        return ($this->special_id && $pack)
            ? $this->special_id
            : $this->original_uuid;
    }






    // originalUuid
    public function getOriginalUuidAttribute()
    {
        return @$this->attributes['uuid'] ?? '';
    }

    // aprilSalary

    public function getAprilSalaryAttribute()
    {
        $userSallary = UserSallary::query()->where(function ($query) {
            $query->where(DB::raw('concat(year,"-", month)'), '<', '2024-4');
        })
            ->where('user_id', $this->id)
            ->where('is_paid', 0)
            //                ->where('user_agency_id', $this->agency_id)
            ->orderByDesc('id')
            ->sum(DB::raw('sallary - cut_amount'));

        return floor($userSallary ?? 0);
    }

    public function getOnlineTimeAttribute($value)
    {
        // Skip pack check if packs relation is not loaded to avoid N+1 queries
        if (!$this->relationLoaded('packs')) {
            return $value;
        }

        if (UserPackHelper::hasHideOnlineTime($this)) {
            return null;
        }

        return $value;
    }

    public function getOrgOnlineTimeAttribute()
    {
        return $this->attributes['online_time'] ?? null;
    }



    public function getRealOnlineTimeAttribute()
    {
        return @$this->attributes['online_time'] ?? $this->online_time;
    }

    public function getPackWithType($type)
    {

        $packs = $this->getLoadedPacks();

        /** @var \Illuminate\Database\Eloquent\Collection $packs */
        return $packs->where('type', $type)->isNotEmpty();
    }

    public function getPackWithTypeV2($type)
    {
        return $this->eligiblePacks->where('type', $type)->isNotEmpty();
    }

    public function getPackWithTypeV3($type)
    {
        if ($this->relationLoaded('packs')) {
            return $this->packs
                ->where('is_used', 1)
                ->where('type', $type)
                ->isNotEmpty();
        }
        return $this->packs()
            ->where('is_used', 1)
            ->where('type', $type)
            ->exists();
    }

    public function nowGame()
    {
        return $this->belongsTo(AllGame::class, 'game_id');
    }

    public function userVips()
    {
        return $this->hasMany(UserVip::class, 'user_id');
    }

    public function getIsFrozenAttribute()
    {
        return optional($this->agency)->is_frozen;
    }

    public function getPhotoAttribute()
    {
        return @$this->profile->avatar;
    }

    public function userType()
    {
        switch ($this->type_user) {
            case 0:
                $userType = __('User');
                break;
            case 1:
                $userType = __('host');
                break;
            case 2:
                $userType = __('Host Agent');
                break;
            case 3:
                $userType = __('Shipping Agent');
                break;
            case 4:
                $userType = __('Resort & Shipping Agent');
                break;
            case 5:
                $userType = __('Admin');
                break;
            default:
                $userType = $this->type_user; // Keep the original value if no match is found
                break;
        }

        return $userType;
    }

    public function userTypeBadge()
    {
        $lang = app()->getLocale() ?? 'en';
        //dd($this->type_user);
        $types = [
            1 => 'host',
            2 => 'agency_owner',
            3 => 'shipping',
            4 => 'bd',
        ];

        $applicableTypes = [];

        if ($this->type_user >= 1) {
            $applicableTypes[1] = $types[1];
        }

        if ($this->type_user == 2) {
            $applicableTypes[2] = $types[2];
        }


        if (ShippingAgency::where('app_owner_id', $this->id)->exists()) {
            $applicableTypes[3] = $types[3];
        }

        if ($this->is_bd) {
            $applicableTypes[4] = $types[4];
        }



        if (empty($applicableTypes)) {
            return $lang === 'ar' ? 'مستخدم' : 'User';
        }

        ksort($applicableTypes);
        $configKeys = [];
        foreach ($applicableTypes as $type) {
            $configKeys[] = "{$lang}_{$type}";
            $configKeys[] = "en_{$type}";
        }

        $configs = ConfigModel::whereIn('name', $configKeys)->get()->keyBy('name');

        $html = '<div class="user-type-badges">';
        foreach ($applicableTypes as $typeName) {

            $localizedKey = "{$lang}_{$typeName}";
            $fallbackKey = "en_{$typeName}";

            $url = $configs[$localizedKey]->value ?? $configs[$fallbackKey]->value ?? null;
            $url = getImagePath($url);
            if ($url) {
                $html .= '<img src="' . e($url) . '" alt="' . e($typeName) . '" style="width: 100px; height: 100px; object-fit: contain; border-radius: 4px; margin-right: 4px;">';
            }
        }

        $html .= '</div>';


        return $html ?: ($lang === 'ar' ? 'مستخدم' : 'User');
    }

    public function userBadge()
    {

        $userBadges = UserBadge::where('user_id', $this->id)->whereHas('badge', fn($q) => $q->where('type', 'regular'))->active()->with("badge.images")->get();

        $html = '<div style="display:flex;flex-wrap:wrap;gap:10px;">';
        foreach ($userBadges as $badge) {
            $badgeImage = $badge->badge?->images?->firstWhere('language', app()->getLocale())?->image
                ?? $badge->badge?->images?->first()?->image
                ?? $badge->badge?->image;
            $url = getImagePath($badgeImage);
            

            if ($url) {
                $html .= '<div style="display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:6px 14px 6px 6px;">'
                    . handleShowImageWithTypes($badge->id, $url, 100, 100, 4, 'contain')
                    . '</div>';
            }
        }
        $html .= '</div>';

        return $html;
    }

    public function userBadgeTop()
    {

        $userBadges = UserBadge::where('user_id', $this->id)->whereHas('badge', fn($q) => $q->where('type', 'top'))->active()->with("badge.images")->get();

        $html = '<div style="display:flex;flex-wrap:wrap;gap:10px;">';
        foreach ($userBadges as $badge) {
            $badgeImage = $badge->badge?->images?->firstWhere('language', app()->getLocale())?->image
                ?? $badge->badge?->images?->first()?->image
                ?? $badge->badge?->image;
            $url = getImagePath($badgeImage);

            if ($url) {
                $html .= '<div style="display:inline-flex;align-items:center;gap:8px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:6px 14px 6px 6px;">'
                    . handleShowImageWithTypes($badge->id, $url, 100, 100, 4, 'contain')
                    . '</div>';
            }
        }
        $html .= '</div>';

        return $html;
    }


    public function wallet()
    {
        return $this->hasOne(UserWallet::class);
    }

    public function momentUserGift()
    {
        return $this->hasMany(MomentUserGift::class, 'user_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function walletTransactionBackups()
    {
        return $this->hasMany(WalletTransactionBackup::class);
    }

    public function shippingAgency()
    {
        return $this->hasOne(ShippingAgency::class, 'app_owner_id');
    }

    public function hasShippingAgency()
    {
        return $this->shippingAgency()->exists();
    }



    public function hostAgency()
    {
        return $this->hasOne(Agency::class, 'app_owner_id');
    }


    public function hasHostAgency()
    {
        return $this->hasOne(Agency::class, 'app_owner_id');
    }

    public function hasShippingAgencyV2()
    {
        return $this->hasOne(ShippingAgency::class, 'app_owner_id');
    }

    public function hasFamily()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
    public function bdSalaries()
    {
        return $this->hasMany(BDSallary::class, 'bd_id');
    }

    public function getBdSalaryAttribute()
    {
        $userSallary = $this->bdSalaries()
            ->sum(DB::raw('sallary - cut_amount'));

        return floor($userSallary);
    }

    public function incrementCutAmountInBdSallary(int $amount)
    {
        $lastBdSalary = $this->bdSalaries()->latest()->first();

        if ($lastBdSalary) {
            $newAmount = max(0, $lastBdSalary->cut_amount + $amount);
            $lastBdSalary->update(['cut_amount' => $newAmount]);

            return true;
        }

        return false;
    }

    public function getUserTypesAttribute(): array
    {
        $userTypes = [];

        if ($this->type_user >= 1) {
            $userTypes[] = 1;
        }

        if ($this->type_user >= 2) {
            $userTypes[] = 2;
        }

        if ($this->relationLoaded('shippingAgency')) {
            if ($this->shippingAgency) {
                $userTypes[] = 3;
            }
        } else {
            $this->loadMissing('shippingAgency');
            if ($this->shippingAgency) {
                $userTypes[] = 3;
            }
        }

        if ($this->is_bd) {
            $userTypes[] = 4;
        }

        $userTypes = array_unique($userTypes);

        return empty($userTypes) ? [0] : $userTypes;
    }


    public function sallariesByMonth()
    {
        return $this->hasMany(UserSallary::class, 'user_id')
            ->where('user_agency_id', $this->agency_id);
    }

    public function latestJoin()
    {
        return $this->hasOne(UsersJoinedAgency::class, 'user_id')
            ->where('agency_id', $this->agency_id)
            ->latestOfMany('join_date');
    }


    public function lastSallary()
    {
        $join = $this->latestJoin()->first();

        return $this->hasOne(UserSallary::class, 'user_id')
            ->where('is_finished', 0)
            ->where(function ($query) use ($join) {
                if ($join) {
                    $start = $join->join_date;
                    $end = $join->leave_date ?? now()->endOfMonth();
                    $query->where('user_agency_id', $this->agency_id)
                        ->where(fn($q) => $q->whereBetween('created_at', [$start, $end])->orWhereBetween('updated_at', [$start, $end]));
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->latestOfMany();
    }

    public function getSalaryByLatestJoinAttribute()
    {
        $join = $this->latestJoin()->first();
        if (!$join) {
            return 0;
        }

        $start = $join->join_date;
        $end =  now();

        $userSalary = UserSallary::query()
            ->where('user_id', $this->id)
            ->where('user_agency_id', $this->agency_id)
            ->whereBetween('created_at', [$start, $end])

            ->sum(DB::raw('sallary - cut_amount'));
        return floor($userSalary * 100) / 100;
    }


    public function type16Packs()
    {
        return $this->hasMany(Pack::class, 'user_id')
            ->where('type', 16)
            ->where('is_used', 1)
            ->where(function ($q) {
                $q->where('expire', 0)
                    ->orWhere('expire', '>=', now()->timestamp);
            });
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     self::saving(function ($model) {
    //         if (request()->has('is_frozen')) {

    //             if ($model->agency) {
    //                 $model->agency->update(['is_frozen' => request()->is_frozen]);
    //             }
    //             request()->request->remove('is_frozen');
    //         }
    //         if (request()->has('charge_agency')) {
    //             if (request('charge_agency') === 1) {
    //                 ChargeAgency::firstOrCreate([
    //                     'agency_id' => $model->agency_id,
    //                 ]);
    //             } else {
    //                 ChargeAgency::where('agency_id', $model->agency_id)->delete();
    //             }
    //         }

    //         $originalProfile = $model->profile;
    //         $newAvatar = request()->input('photo');
    //         if ($originalProfile && $newAvatar && $originalProfile->avatar !== $newAvatar) {

    //             $newCount = $model->profile_count + 1;
    //             $model->profile_count = $newCount;

    //             $file = request('photo');
    //             if ($file instanceof UploadedFile) {

    //                 $url = Common::uploadProfileUser('profile', $file, $originalProfile->id, $newCount);
    //                 Storage::delete($model->profile->avatar);
    //             }
    //             if ($model->profile) {
    //                 $model->profile->avatar = $url ?? '';
    //             }
    //         } else {
    //             if ($model->profile) {
    //                 $model->profile->avatar = $model->profile->avatar;
    //             }
    //         }
    //         unset($model->photo);
    //         unset($model->original_uuid);
    //     });

    //     self::updating(function ($user) {
    //         // Check if coins increased
    //         $originalCoins = $user->getOriginal('di');
    //         $newCoins = $user->di;

    //         if ($newCoins > $originalCoins) {
    //             $user->new_gift = true;
    //         }
    //         if ($user->agency_id) {
    //             clearAgencyCache($user->agency_id);
    //         }

    //         static::deleted(function ($user) {
    //             if ($user->agency_id) {
    //                 clearAgencyCache($user->agency_id);
    //             }
    //         });
    //         // Handle profile.avatar update

    //     });
    // }
    protected static function boot()
    {
        parent::boot();

        static::saving(function (User $user) {
            self::handleAgencyFreeze($user);
            self::handleChargeAgency($user);
            self::handleProfilePhoto($user);
            unset($user->photo, $user->original_uuid);
        });

        static::saved(function (User $user) {
            self::ensureLetterAvatar($user);
        });

        static::updating(function (User $user) {
            if ($user->di > $user->getOriginal('di')) {
                $user->new_gift = true;
            }
            if ($user->agency_id) {
                clearAgencyCache($user->agency_id);
            }
        });

        static::updated(function (User $user) {
            \Cache::forget("data_user_{$user->id}");
            \Cache::forget("user_rooms_{$user->id}");
        });

        static::deleted(function (User $user) {
            \Cache::forget("data_user_{$user->id}");
            \Cache::forget("user_rooms_{$user->id}");
            if ($user->agency_id) {
                clearAgencyCache($user->agency_id);
            }
        });
    }

    protected static function handleAgencyFreeze(User $user)
    {
        if (!request()->filled('is_frozen') || !$user->agency_id) {
            return;
        }

        $new = request('is_frozen');

        if ($user->agency && $user->agency->is_frozen != $new) {
            $user->agency->update(['is_frozen' => $new]);
        }

        request()->request->remove('is_frozen');
    }

    protected static function handleChargeAgency(User $user)
    {
        if (!request()->has('charge_agency') || !$user->agency_id) {
            return;
        }

        $charge = (int) request('charge_agency') === 1;

        if ($charge) {
            ChargeAgency::firstOrCreate(['agency_id' => $user->agency_id]);
        } else {
            ChargeAgency::where('agency_id', $user->agency_id)->delete();
        }
    }

    /**
     * Server-side letter avatar: when the user has a name but no avatar, generate
     * a real stored "first letter on colored background" image and save it on the
     * profile, exactly as if uploaded. Idempotent (never overwrites an existing
     * avatar) and skips the official/system account. Runs after save so the id and
     * the auto-created profile row exist.
     */
    protected static function ensureLetterAvatar(User $user): void
    {
        if (in_array((int) $user->id, config('letter_avatar.excluded_user_ids', []), true)) {
            return;
        }

        $name = trim((string) ($user->getAttributes()['name'] ?? ''));
        if ($name === '') {
            return;
        }

        $profile = $user->profile()->first();
        if (!$profile || !empty($profile->avatar)) {
            return;
        }

        $path = app(\App\Services\LetterAvatarService::class)
            ->generate('profile', $name, $user->id);

        if ($path) {
            $profile->update(['avatar' => $path]);
        }
    }

    protected static function handleProfilePhoto(User $user)
    {
        $file = request()->file('photo');

        if (!$file instanceof \Illuminate\Http\UploadedFile) {
            return;
        }

        $profile = $user->profile()->first();
        if (!$profile) {
            return;
        }

        $newCount = $user->profile_count + 1;
        $user->profile_count = $newCount;

        $url = Common::uploadProfileUser('profile', $file, $profile->id, $newCount);

        if ($profile->avatar && Storage::exists($profile->avatar)) {
            Storage::delete($profile->avatar);
        }

        $profile->update(['avatar' => $url]);
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlackList::class, 'user_id');
    }

    public function blockedMe()
    {
        return $this->hasMany(BlackList::class, 'from_uid');
    }

    public function getProfileFrame(): Ware | null
    {
        return $this->packs?->where('type', 28)->where('is_used', 1)->first()?->ware;
    }
    public function myGifts()
    {
        return $this->belongsToMany(Gift::class, 'user_gifts')
            ->withPivot('quantity', 'expire')
            ->withTimestamps()
            ->where(function ($query) {
                $query->where('user_gifts.expire', 0)
                    ->orWhereRaw('DATE_ADD(user_gifts.created_at, INTERVAL user_gifts.expire DAY) > NOW()');
            });
    }


    public function activePack20()
    {
        return $this->hasOne(Pack::class)
            ->where('is_used', 1)
            ->where('type', 20)
            ->where(function ($q) {
                $q->where('expire', 0)
                    ->orWhere('expire', '>=', now()->timestamp);
            });
    }



    public function giftLogs()
    {
        return $this->hasMany(GiftLog::class, 'receiver_id');
    }



    public function blacklists()
    {
        return $this->hasMany(BlackList::class, 'user_id', 'id')
            ->where('status', 1);
    }


    public function chatSetting()
    {
        return $this->hasOne(\App\Models\ChatSetting::class, 'user_id')
            ->withDefault([
                'chat_with_friends' => 1,
                'chat_with_all' => 0,
            ]);
    }

    public function userDataSetting()
    {
        return $this->hasOne(\App\Models\UserSetting::class, 'user_id')
            ->withDefault([
                'show_git'   => 1,
                'show_intro' => 1,
                'show_banner' => 1,
            ]);
    }



    public function getVipDataAttribute()
    {
        $vip = $this->Ovip;
        if (!$vip) {
            return new \stdClass();
        }

        $vipIcon = $vip->wareIcon;
        $hasColor = Common::hasInPackV2($this->packs, 18, true);
        $color    = Common::hasColorInPackV2($this->packs, 21, true);

        $vip_gifts = $vip->privilegs->contains(fn($priv) => $priv->type == 14);
        $vip_upload_gif = $vip->privilegs->contains(fn($priv) => $priv->type == 22);

        return [
            'id'             => 1,
            'level'          => $vip->level ?? 0,
            'name'           => $vip->name ?? '',
            'price'          => $vip->price ?? 0,
            'img_old'        => $vip->img ?? '',
            'img'            => $vipIcon->show_img ?? '',
            'image'          => $vip->image ?? '',
            'image_from_wares' => $vipIcon->show_img ?? '',
            'expire'         => $vip->expire ?? 0,
            'ware_id'        => $vipIcon?->id ?? 0,
            'color'          => $color ?? '',
            'vip_gifts'      => $vip_gifts ?? 0,
            'vip_upload_gif' => $vip_upload_gif ?? 0,
            'colored_name'   => (fn($c) => is_string($c) ? $c : '')($hasColor ? Common::wareUserVipV2($this, 18, 'color') : null),
        ];
    }






    public function getLevelDataAttribute()
    {
        $expPercentages  = Config::get('exp_percentages') ?? [0, 0];

        $diamondReceived = $this->total_received_diamonds ?? 0;
        $diamondSend     = $this->total_sender_diamonds ?? 0;

        $receivedNum = floor($diamondReceived * ($expPercentages['exp_received_percentage'] ?? 1));
        $senderNum   = floor($diamondSend * ($expPercentages['exp_sender_percentage'] ?? 1));

        $star_level  = $this->total_received_level ?? 0;
        $gold_level  = $this->total_sender_level ?? 0;

        $vipsData = \Cache::rememberForever(
            'vips_data',
            fn() =>
            Vip::all()->groupBy('type')
        );

        $firstVip_type1 = $vipsData[1]->firstWhere('level', $star_level);
        $firstVip_type2 = $vipsData[2]->firstWhere('level', $gold_level);

        $star_level_img = $firstVip_type1->img ?? '';
        $gold_level_img = $firstVip_type2->img ?? '';

        $current_star_num = $this->getCurrentLevelFromCache(1, $star_level, 'exp', $vipsData);
        $current_gold_num = $this->getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);

        $nextStarData = $this->getNextLevelDataFromCache(1, $star_level, $vipsData);
        $nextGoldData = $this->getNextLevelDataFromCache(2, $gold_level, $vipsData);

        $next_star_num    = $nextStarData['next_exp'];
        $next_star_level  = $nextStarData['next_level'];
        $next_gold_num    = $nextGoldData['next_exp'];
        $next_gold_level  = $nextGoldData['next_level'];

        return [
            'receiver_num'        => $receivedNum,
            'receiver_img'        => $star_level_img,
            'sender_num'          => $senderNum,
            'sender_rem'          => max(0, $next_gold_num - $senderNum),
            'receiver_rem'        => max(0, $next_star_num - $receivedNum),
            'sender_img'          => $gold_level_img,
            'receiver_level'      => $star_level,
            'next_receiver_num'   => $next_star_num ?: 0,
            'next_receiver_level' => $next_star_level ?: 0,
            'sender_level'        => $gold_level,
            'next_sender_num'     => $next_gold_num ?: 0,
            'next_sender_level'   => $next_gold_level ?: 0,
            'prev_receiver_num'   => $current_star_num ?: 0,
            'prev_sender_num'     => $current_gold_num ?: 0,
            'current_receiver_num' => $current_star_num,
            'current_sender_num'  => $current_gold_num,
            'exp-sender'          => $expPercentages['exp_sender_percentage'] ?? 1,
            'exp-receiver'        => $expPercentages['exp_received_percentage'] ?? 1,
            'receiver_per'        => $this->calcPercentage($receivedNum, $current_star_num, $next_star_num),
            'sender_per'          => $this->calcPercentage($senderNum, $current_gold_num, $next_gold_num),
        ];
    }

    private function calcPercentage($current, $prev, $next)
    {
        $range = $next - $prev;
        $progress = $current - $prev;
        if ($range <= 0) return 0.0;
        $percentage = $progress / $range;
        return $percentage < 0 ? 0.0 : ($percentage > 1 ? 1.0 : round($percentage, 2));
    }

    private  function getCurrentLevelFromCache($type = null, $level = 0, $field = null, $vipsData = [])
    {
        if (!$type || !$field || !isset($vipsData[$type])) return 0;

        $levelData = $vipsData[$type]->where('level', '=', $level)->first();

        return $levelData ? $levelData->$field : 0;
    }

    private  function getNextLevelDataFromCache($type, $currentLevel, $vipsData)
    {
        if (!isset($vipsData[$type])) return ['next_exp' => 0, 'next_level' => 0];

        $data = $vipsData[$type];
        $nextData = ['next_exp' => 0, 'next_level' => 0];

        foreach ($data as $row) {
            if ($row->level > $currentLevel) {
                $nextData['next_exp'] = $row->exp;
                $nextData['next_level'] = $row->level;
                break;
            }
        }

        if ($nextData['next_exp'] == 0) {
            $nextData['next_exp'] = $data->last()->exp ?? 0;
        }

        if ($nextData['next_level'] == 0) {
            $nextData['next_level'] = $data->last()->level ?? 0;
        }

        return $nextData;
    }

    public function getProfileFrameAttribute()
    {
        return Common::wareUserVipV2($this, 28, 'img2', true);
    }

    public function getProfileFrameIdAttribute()
    {
        return Common::wareUserVipV2($this, 28, 'id', true);
    }


    public function nowRoomOwner(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Room::class,
            'id',
            'id',
            'now_room_uid',
            'uid'
        );
    }



    public function coinLogs()
    {
        return $this->morphMany(CoinLog::class, 'owner', 'user_type', 'user_id');
    }


    public function agencyJobs()
    {
        return $this->hasMany(AgencyUserJob::class, 'user_id');
    }

    public function getIsAdminInAgencyAttribute()
    {
        return $this->agencyJobs()->where('type', 'requestManger')->exists();
    }

    public function roomVisitors()
    {
        return $this->hasMany(RoomVisitor::class, 'user_id');
    }
    public function liveTimes()
    {
        return $this->hasMany(LiveTime::class, 'uid');
    }

    public function hostLevelWinner()
    {
        return $this->hasOne(HostLevelWinner::class, 'user_id', 'id');
    }


    public function hostLevelWinnerByLevelAndEvent($hostLevelId)
    {
        $eventType = Common::getSettingValue('host_level_type') ?? 'daily';
        return $this->hostLevelWinner()
            ->where('host_level_id', $hostLevelId)
            ->filterByEventType($eventType)->exists();
    }

    public function lastHostLevelWinner()
    {
        return $this->hasOne(HostLevelWinner::class, 'user_id', 'id')->latest('created_at');
    }

    /**
     * Optionally, filter by event type
     */
    public function lastHostLevelWinnerByEvent($eventType)
    {
        return $this->lastHostLevelWinner()->filterByEventType($eventType);
    }

    public function userWallet()
    {
        return $this->hasOne(UserWallet::class);
    }

    public function getUserWalletBalanceAttribute()
    {
        $wallet = $this->userWallet;

        if (!$wallet) {
            return 0;
        }

        return (float)($wallet->balance ?? 0)
            - (float)($wallet->cut_amount ?? 0)
            - (float)($wallet->pending_amount ?? 0);
    }
}
