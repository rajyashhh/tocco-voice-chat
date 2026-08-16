<?php

namespace App\Models;

use App\Traits\AutoReceiveType;
use App\Traits\TimestampsWithTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Vip\Entities\UserVip;

class Pack extends Model
{
    use SoftDeletes, TimestampsWithTimezone, AutoReceiveType;

    protected $guarded = [];

    protected $dates = ['deleted_at'];

    public function ware()
    {
        return $this->belongsTo(Ware::class, 'target_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function senderable(): MorphTo
    {
        return $this->morphTo(null, 'sender_type', 'sender_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'dash_user_id');
    }

    public function userVip()
    {
        return $this->belongsTo(UserVip::class, 'vip_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->where('is_used', 1);
    }

    public function getTypeGet()
    {
        switch ($this->get_type) {
            case 1:
                return __('vip level automatic acquisition');
            case 2:
                return __('activities');
            case 3:
                return __('treasure box');
            case 4:
                return __('purchase');
            case 5:
                return __('background addition');
            case 6:
                return __('limited time purchase');
            default:
                return '-';
        }
    }

    public function getType()
    {
        switch ($this->type) {
            case 1:
                return trans('Gemstone');
            case 3:
                return trans('Card Scroll');
            case 4:
                return trans('Avatar Frame');
            case 5:
                return trans('Bubble Frame');
            case 6:
                return trans('Entering Special Effects');
            case 7:
                return trans('Microphone Aperture');
            case 8:
                return trans('Badge');
            case 9:
                return trans('NoKick');
            case 10:
                return trans('Icon');
            case 11:
                return trans('intro animation');
            case 12:
                return trans('wapel');
            case 13:
                return trans('hide country');
            case 14:
                return trans('vip gifts');
            case 15:
                return trans('no pan');
            case 16:
                return trans('hidden room');
            case 17:
                return trans('anonymous man');
            case 18:
                return trans('colored name');
            case 19:
                return trans('profile visitors hide in');
            case 20:
                return trans('hide last active');
            case 21:
                return trans('sound effect');
            case 22:
                return trans('upload GIF image');
            case 25:
                return trans('special uuid');
            case 28:
                return trans('profile frame');
            default:
                return '-';
        }
    }



    public function getTypeNameAttribute()
    {
        $types = [
            1 => 'gem',
            2 => 'gifts',
            3 => 'coupons',
            4 => 'avatar frames',
            5 => 'bubble boxes',
            6 => 'entry effects',
            7 => 'mic on the aperture',
            8 => 'badges',
            28 => 'profile frame',
            25 => 'special id',
        ];
        return __($types[$this->type] ?? '');
    }

    public function getGetTypeNameAttribute()
    {
        $getTypes = [
            1 => 'vip level automatic acquisition',
            2 => 'activities',
            3 => 'treasure box',
            4 => 'purchase',
            5 => 'background addition',
            6 => 'limited time purchase'
        ];
        return __($getTypes[$this->get_type] ?? '');
    }

    public function getFormattedExpireAttribute()
    {
        if (!is_null($this->expire)) {
            return date('Y-m-d H:i:s', $this->expire);
        }

        // if expire is NULL
        return (string) ($this->days ?? 0);;
    }

    public function getIsDressAttribute()
    {
        $map = [
            4 => 'dress_1',
            5 => 'dress_2',
            6 => 'dress_3',
            7 => 'dress_4',
        ];

        if (!in_array($this->type, array_keys($map))) {
            return 0;
        }

        $column = $map[$this->type];
        $userDressId = $this->user?->$column;

        return $userDressId == $this->target_id ? 1 : 0;
    }

    public function getTitleTextAttribute()
    {
        if (in_array($this->type, [4, 5, 6, 7])) {
            return $this->expire
                ? date('Y-m-d H:i:s', $this->expire) . ' expire'
                : 'permanent';
        }

        if ($this->type == 2) {
            return "have {$this->num} value " . ($this->num * $this->price) . " diamond";
        }

        return "have {$this->num} individual {$this->title}";
    }

    public function getResolvedColorAttribute()
    {
        return $this->color ?? '';
    }

    public function getCreatedAtFormattedAttribute()
    {
        $tz = request()->header('tz')[0] ?? 'UTC';
        // Validate timezone
        if (!in_array($tz, timezone_identifiers_list())) {
            $tz = 'UTC'; // Fallback
        }
        return Carbon::parse($this->created_at)->setTimezone($tz)->format('Y-m-d H:i:s');
    }
}
