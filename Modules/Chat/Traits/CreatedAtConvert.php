<?php
namespace Modules\Chat\Traits;

use App\Helpers\UserCommon;
use Carbon\Carbon;

trait CreatedAtConvert {


    function create_at($timeZone = null, $createdAt = null){
        $createdAt = $createdAt ?? now();
        $createdAt = Carbon::parse($createdAt)->setTimezone($timeZone);

        if ($createdAt->isCurrentHour() || $createdAt->isCurrentDay()) {
            if (app()->getLocale() == 'ar') {
                return UserCommon::englishToArabicNumbers($createdAt);
            }
            return $createdAt->isoFormat('h:mm:ss A');
        }
        else if ($createdAt->isYesterday()) {
            return __('messages.yesterday');
        }
        else if ($createdAt->isCurrentWeek()) {
            $dayName = $createdAt->locale(app()->getLocale())->dayName; // ترجم اسم اليوم
            return $dayName;
        }
        else if ($createdAt->isCurrentDay()) {
            $daysSinceCreation = $createdAt->diffInHours(Carbon::now());
            return __('messages.days_ago', ['days' => $daysSinceCreation]);
        }
        else {
             if (app()->getLocale() == 'ar') {
                return UserCommon::englishToArabicNumbersDate($createdAt);
            }
            return $createdAt->locale(app()->getLocale())->format('Y-m-d');
        }

    }

}

