<?php

namespace App\Providers;

use App\helper\TimeHelper;
use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class TimeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Carbon::setWeekStartsAt(TimeHelper::startOfWeekConst());
        Carbon::setWeekEndsAt(TimeHelper::endOfWeekConst());
    }
}
