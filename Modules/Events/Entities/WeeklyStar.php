<?php

namespace Modules\Events\Entities;

use App\Models\Gift;
use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Modules\CP\Entities\WeeklyCpGift;
use Modules\CP\Entities\WeeklyCpWinner;
use Modules\CP\Traits\CpWeeklyStar;
use Modules\Events\Traits\EventModel;

class WeeklyStar extends Model
{
    use EventModel, HasFactory, SoftDeletes, TimestampsWithTimezone, CpWeeklyStar;

    protected $guarded = [];

    protected $appends = ['start_date_local', 'end_date_local'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function gifts()
    {
        return $this->belongsToMany(Gift::class, 'weekly_star_gifts', 'weekly_star_id', 'gift_id');
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class, 'weekly_star_id');
    }

    public function weeklyCpGifts()
    {
        return $this->hasMany(WeeklyCpGift::class, 'weekly_cp_id');
    }

    public function WeeklyStarGifts()
    {
        return $this->hasMany(WeeklyStarGift::class, 'weekly_star_id');
    }

    public function WeeklyCpWinners()
    {
        return $this->hasMany(WeeklyCpWinner::class, 'weekly_cp_id');
    }



    public function scopeWeeklyStar(Builder $query)
    {
        return $query->where('type', 'weekly_star');
    }

    public function scopePeriod(Builder $query)
    {
        return $query->where('type', 'event_period');
    }


    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->start_date = self::convertArabicNumbers($model->attributes['start_date']);
            if ($model->type === 'event_period') {
                $model->end_date = self::convertArabicNumbers($model->attributes['end_date']);
            } else {
                $model->end_date = Carbon::createFromFormat('Y-m-d', $model->attributes['start_date'])->addWeek();
            }
            $model->admin_id = Auth::id() ?? ($model->admin_id ?? null);
        });

        self::saving(function ($model) {
            if ($model->isDirty('start_date')) {
                $model->start_date = self::convertArabicNumbers($model->attributes['start_date']);
                if ($model->type === 'event_period') {
                    $model->end_date = self::convertArabicNumbers($model->attributes['end_date']);
                } else {
                    $model->end_date = Carbon::createFromFormat('Y-m-d', $model->attributes['start_date'])->addWeek();
                }
                $model->editor_id = Auth::id();
            }
        });
    }


    protected static function convertArabicNumbers($string)
    {
        $newNumbers = range(0, 9);
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($arabicNumbers, $newNumbers, $string);
    }
}
