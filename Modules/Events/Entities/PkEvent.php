<?php

namespace Modules\Events\Entities;

use App\Models\User;
use App\Traits\TimestampsWithTimezone;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Events\Traits\EventModel;

class PkEvent extends Model
{
    use EventModel, HasFactory, TimestampsWithTimezone;

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

    public function rewards()
    {
        return $this->hasMany(PkReward::class, 'pk_event_id');
    }

    public function WinnersPK()
    {
        return $this->hasMany(PkWinner::class, 'pk_event_id');
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $dataLang = self::checkDateLanguage($model->attributes['start_date']);
            if ($dataLang === 'arabic') {

                $model->start_date = self::convertArabicNumbers($model->attributes['start_date']);
            } else {
                $model->start_date = $model->attributes['start_date'];
            }
            //  $model->start_date = self::convertArabicNumbers($model->attributes['start_date']);
            $model->end_date = Carbon::createFromFormat('Y-m-d', $model->attributes['start_date'])->addWeek();
            $model->admin_id = Auth::id();
        });

        self::saving(function ($model) {
            if ($model->isDirty('start_date')) {
                $dataLang = self::checkDateLanguage($model->attributes['start_date']);
                if ($dataLang === 'arabic') {

                    $model->start_date = self::convertArabicNumbers($model->attributes['start_date']);
                } else {
                    $model->start_date = $model->attributes['start_date'];
                }
                //  $model->start_date = self::convertArabicNumbers($model->attributes['start_date']);
                $model->end_date = Carbon::createFromFormat('Y-m-d', $model->attributes['start_date'])->addWeek();
                $model->editor_id = Auth::id();
                // $model->admin_id = Auth::id();
            }
        });
    }

    /* protected static function convertArabicNumbers($string) {
        $newNumbers = range(0, 9);
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return str_replace($arabicNumbers, $newNumbers, $string);
    }

    public function getStartDateAttribute($value)
    {
        $date = self::convertArabicNumbers($value);
        return Carbon::parse($date, '-03:00')->subDay()->startOfDay();

    }
    public function getEndDateAttribute($value)
    {
        return Carbon::parse($value)->subDay()->endOfDay();
    }

    public function getStartDateLocalAttribute()
    {
        return Carbon::parse($this->attributes['start_date'])->subDay()->toDateString();
    }
    public function getEndDateLocalAttribute($value)
    {
        return Carbon::parse($this->attributes['end_date'])->subDay()->toDateString();
    }*/
}
