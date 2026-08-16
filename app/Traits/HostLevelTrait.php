<?php

namespace App\Traits;

use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;



trait HostLevelTrait
{


    public function scopeFilterByEventType(Builder $query, $eventType)
    {
        $timezone = Common::timeZone();
        return $query->when($eventType == 'daily', function ($query) use ($timezone) {
            $query->whereDate('created_at', Carbon::now($timezone)->toDateString());
        })
            ->when($eventType == 'weekly', function ($query) use ($timezone) {
                $startOfWeek = Carbon::now($timezone)->startOfWeek();
                $endOfWeek   = Carbon::now($timezone)->endOfWeek();

                $query->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            })
            ->when($eventType == 'monthly', function ($query) use ($timezone) {
                $startOfMonth = Carbon::now($timezone)->startOfMonth();
                $endOfMonth   = Carbon::now($timezone)->endOfMonth();

                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            });
    }
}
