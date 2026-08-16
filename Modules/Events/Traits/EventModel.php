<?php

namespace Modules\Events\Traits;

use App\Helpers\LogHelper;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Database\Eloquent\Builder;

trait EventModel
{
    public function getStartDateAttribute($value)
    {
        $date = self::convertArabicNumbers($value);
        $timezone = getTimezone();
        return \Carbon\Carbon::parse($date, $timezone)->timezone('UTC')->toDateTimeString();
    }
    public function getEndDateAttribute($value)
    {
        $timezone = getTimezone();

        return \Carbon\Carbon::parse($value, $timezone)->timezone('UTC')->toDateTimeString();
    }

    public function scopePreviousNewEvent(Builder $query)
    {
        // $timezone = config('app.owner_timezone');
        $timezone = Common::timeZone();

        // Define start and end of the week
        $nowDate = Carbon::now($timezone)->toDateTimeString();

        // 7 days ago, starting from midnight (00:00:00)
        $weekStart = Carbon::now($timezone)->subDays(7)->startOfDay()->toDateTimeString();

        return $query->whereRaw("CONVERT_TZ(end_date, '+00:00', ?) BETWEEN ? AND ?", [
            $timezone,
            $weekStart,
            $nowDate
        ]);
    }

    public function getStartDateLocalAttribute()
    {
        return Carbon::parse($this->attributes['start_date'])->toDateString();
    }
    public function getEndDateLocalAttribute($value)
    {
        return Carbon::parse($this->attributes['end_date'])->toDateString();
    }

    public function setStartDateLocalAttribute($value)
    {
        $this->attributes['start_date'] = $value;
    }


    public static function convertArabicNumbers($string)
    {
        $newNumbers = range(0, 9);
        $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return str_replace($arabicNumbers, $newNumbers, $string);
    }

    public static function checkDateLanguage($date)
    {
        $arabicNumbersPattern = '/[٠-٩]/u'; // Arabic numerals
        $englishNumbersPattern = '/[0-9]/'; // English numerals

        if (preg_match($arabicNumbersPattern, $date)) {
            return 'arabic';
        }

        if (preg_match($englishNumbersPattern, $date)) {
            return 'english';
        }

        return 'unknown';
    }


    public function scopeCurrentEvent(Builder $query)
    {
        // $timezone = config('app.owner_timezone') ?? '-03:00';
        $timezone = Common::timeZone();
        $nowDate     = Carbon::now()->copy()->timezone($timezone)->toDateTimeString();
        return $query->whereRaw("start_date <= ?", [$nowDate])->whereDate('end_date', '>=', $nowDate); // 27
        // ->whereRaw("CONVERT_TZ(end_date, '+00:00', ?) >= ?", [$timezone, $nowDate]); // 27
    }


    public function scopePreviousEvent(Builder $query)
    {

        $timezone = getTimezone(); // Example: 'Africa/Cairo'
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s');

        return $query
            ->whereRaw("
            CONVERT_TZ(CONCAT(end_date, ' 00:00:00'), ?, '+00:00') <= ?
        ", [$timezone, $now])
            ->orderByDesc('end_date');
    }


    //    public function scopePreviousEvent(Builder $query)
    //    {
    //        return $query
    //            ->whereDate('end_date', '<', now(getTimezone())->toDateString())
    //            ->orderByDesc('end_date');
    //    }

    public function scopeEndToday(Builder $query)
    {
        $timezone = getTimezone(); // e.g., "Africa/Cairo"
        $nowDate  = Carbon::now($timezone)->toDateString(); // e.g., "2025-07-13"

        return $query->whereRaw(
            "DATE(CONVERT_TZ(end_date, '+00:00', ?)) = ?",
            [$timezone, $nowDate]
        );
    }

    public function scopeDayEnd(Builder $query): Builder
    {
        $timezone = getTimezone();
        $nowDate  = Carbon::now($timezone)->toDateString();

        return $query->whereDate('end_date', $nowDate);
    }
}
