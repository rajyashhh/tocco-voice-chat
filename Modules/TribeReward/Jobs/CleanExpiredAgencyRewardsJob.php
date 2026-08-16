<?php

namespace Modules\TribeReward\Jobs;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\GiftLog;
use Carbon\Carbon;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\TribeReward\Entities\AgencyReward;
use Modules\TribeReward\Entities\TribePeriod;
use Modules\TribeReward\Entities\TribeReward;
use Modules\TribeReward\Entities\TribeTop;

class CleanExpiredAgencyRewardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        try {
            $now = Carbon::now();

            $deleted = AgencyReward::where('expire_at', '<', $now)->delete();

            info("Cleaned up $deleted expired agency rewards.");

        } catch (\Throwable $e) {
          //  \Log::error("ERROR in CleanExpiredAgencyRewardsJob: " . $e->getMessage());
            throw $e;
        }
    }
}
