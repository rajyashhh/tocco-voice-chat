<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\GiftLog;
use App\Models\Gift;
class GiftUpdateUsedCountMonthly extends Command
{

    protected $signature = 'update-gift-monthly:cron';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $gifts = Gift::orderBy("use_count", "asc")->where("enable",1)->take(5)->get();
        if ($gifts) {
            foreach ($gifts as $gift) {
                $gift->enable=2;
                $gift->save();
            }
        }
    }
}
