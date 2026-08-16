<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HomeCarouselDisplay extends Model
{
    use HasFactory;

    protected $table = 'home_carousel_displays';

    protected $fillable = [
        'home_carousel_id',
        'display_type',
        'created_at',
        'end_at',
        'duration',
        'duration_unit',
    ];

    protected $dates = [
        'created_at',
        'end_at',
    ];

    /**
     * العلاقة مع HomeCarousel
     */
    public function carousel()
    {
        return $this->belongsTo(HomeCarousel::class, 'home_carousel_id');
    }


    public function getRemainingTimeAttribute()
    {
        if ($this->end_at) {
            $now = Carbon::now();
            if ($now->gt($this->end_at)) {
                return 0;
            }
            return $now->diffInSeconds($this->end_at);
        }
        return null;
    }

    /**
     *
     * @param int $amount
     * @return void
     */

    public function addDuration(int $amount)
    {
        if (!$this->end_at) {
            $this->end_at = Carbon::now();
        }

        switch ($this->duration_unit) {
            case 'hours':
                $this->end_at->addHours($amount);
                break;
            case 'days':
                $this->end_at->addDays($amount);
                break;
            case 'months':
                $this->end_at->addMonths($amount);
                break;
            case 'lifetime':
                // لا تفعل شيئًا لأن المدة غير محدودة
                break;
            default:
                $this->end_at->addHours($amount);
                break;
        }

        // ✅ منع تجاوز تاريخ MySQL الأقصى
        $maxDate = Carbon::create(9999, 12, 31, 23, 59, 59);
        if ($this->end_at->greaterThan($maxDate)) {
            $this->end_at = $maxDate;
        }

        $this->duration += $amount;
        $this->save();
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->created_at = $model->created_at ?? Carbon::now();
            $endAt = clone $model->created_at;

            switch ($model->duration_unit) {
                case 'hours':
                    $endAt->addHours($model->duration);
                    break;
                case 'days':
                    $endAt->addDays($model->duration);
                    break;
                case 'months':
                    $endAt->addMonths($model->duration);
                    break;
                case 'lifetime':
                    $endAt = null;
                    break;
                default:
                    $endAt->addHours($model->duration);
                    break;
            }

            $maxDate = Carbon::now()->addYears(12);
            if ($endAt && $endAt->greaterThan($maxDate)) {
                $endAt = $maxDate;
            }

            $model->end_at = $endAt;
        });

        self::updating(function ($model) {
            if ($model->isDirty(['duration', 'duration_unit'])) {
                $startAt = $model->created_at ?? Carbon::now();
                $endAt = clone $startAt;

                switch ($model->duration_unit) {
                    case 'hours':
                        $endAt->addHours($model->duration);
                        break;
                    case 'days':
                        $endAt->addDays($model->duration);
                        break;
                    case 'months':
                        $endAt->addMonths($model->duration);
                        break;
                    case 'lifetime':
                        $endAt = null;
                        break;
                    default:
                        $endAt->addHours($model->duration);
                        break;
                }
                $maxDate = Carbon::now()->addYears(12);
                if ($endAt && $endAt->greaterThan($maxDate)) {
                    $endAt = $maxDate;
                }

                $model->end_at = $endAt;
            }
        });
    }
}
