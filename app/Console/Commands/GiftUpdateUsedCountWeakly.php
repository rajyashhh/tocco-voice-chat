<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\GiftLog;
use App\Models\Gift;
class GiftUpdateUsedCountWeakly extends Command
{

    protected $signature = 'update-gift-weakly:cron';

    protected $description = 'Command description';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Gift::query()->update(['use_count' => 0]);
        $currentDateTime = \Carbon\Carbon::now();
        $oneMonthFromNow = $currentDateTime->subMonth();
        $gift_logs = GiftLog::query()->with("gift")->select(['giftId', DB::raw('sum(giftNum) as total')])
                    ->whereDate("created_at","<=",date("Y-m-d"))
                    ->whereDate("created_at",">=",$oneMonthFromNow)
                    ->groupBy('giftId')
                    ->get();

//        if ($gift_logs) {
            foreach ($gift_logs as $gift_log) {
                $gift=Gift::find($gift_log?->giftId);
                if (!$gift) continue;
                $gift->use_count =$gift_log?->total ?? 0;
                $gift->save();
            }
//        }

        // The mass use_count reset above is eventless and gift lists order by
        // use_count DESC, so invalidate the API gift catalog once at the end.
        try {
            Cache::tags(['gifts'])->flush();
        } catch (\Exception $e) {
            \Log::warning('Failed to flush gifts cache in GiftUpdateUsedCountWeakly: ' . $e->getMessage());
        }

//        $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');
    }
}
