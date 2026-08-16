<?php

namespace Modules\FixedTarget\Classes;

use App\Helpers\Common;
use App\Models\Target;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\FixedTarget\Interfaces\TargetInterface;

class RegularTarget implements TargetInterface
{

    public function getTarget(int $diamond): Model|null
    {
        return Target::query()->where('diamonds', '<=', $diamond)->orderBy('diamonds', 'desc')->first();
    }

    public function calculateUsdFromTarget(Model $target, float $hours, int $days, array $extra): float
    {
       
        $targetReel =  explode(',', $target->reel);
        $targetMoment = explode(',', $target->moment);
        $extras = $extra;
        // $per = 0.50;
        $per = common::getDiamondsPercentage();
       
        if ($target->hours <= $hours) {
            $per += (((int) Common::getSettingsValue('hours')) ?? 0) / 100;
        }
        // logger('hours Achieved:', [$per]);



        if ($target->days <= $days) {
            $per +=  (((int) Common::getSettingsValue('days')) ?? 0) / 100;
        }
        // logger('days Achieved:', [$per]);


        if (((@$targetMoment[0] ?? 0) <= ($extras['moment']['upload'] ?? 0)) && ((@$targetMoment[1] ?? 0) <= ($extras['moment']['likes']) ?? 0) && ((@$targetMoment[2] ?? 0) <= (@$extras['moment']['comments'] ?? 0))) {

            $per += (((int)Common::getSettingsValue('moments')) ?? 0) / 100;
        }
        // logger('targetMoment Achieved:', [$per]);

        if (((@$targetReel[0] ?? 0) <= ($extras['reel']['upload'] ?? 0)) && ((@$targetReel[1] ?? 0) <= ($extras['reel']['likes'] ?? 0)) && ((@$targetReel[2] ?? 0) <= ($extras['reel']['comments'] ?? 0))) {
            $per += (((int)Common::getSettingsValue('reels')) ?? 0) / 100;
        }
        // logger('targetReel Achieved:', [$per]);

        //        if (Common::getConf('all_target_or_nothing') == 'true') {
        //            if ($per < 1) {
        //                $per = 0;
        //            }
        //        }
        $usd = Common::getTargetUsd($target->diamonds, $target->usd);
        return $usd * $per;
    }

    public function calculatePercentageAchieved(Model $target, float $hours, int $days, array $extra): float
    {
        $targetReel =  explode(',', $target->reel);
        $targetMoment = explode(',', $target->moment);
        $extras = $extra;
        // $per = 0.50;
        $per = common::getDiamondsPercentage();
        // logger('getDiamondsPercentage Achieved:', [$per]);
        if ($target->hours <= $hours) {
            $per += (((int) Common::getSettingsValue('hours')) ?? 0) / 100;

        }
        // logger('hours Achieved:', [$per]);


        if ($target->days <= $days) {
            $per += (((int) Common::getSettingsValue('days')) ?? 0) / 100;

        }

        // logger('days Achieved:', [$per]);

        if ((@$targetMoment[0] ?? 0) <= $extras['moment']['upload'] && (@$targetMoment[1] ?? 0) <= $extras['moment']['likes'] && (@$targetMoment[2] ?? 0) <= $extras['moment']['comments']) {

            $per += (((int) Common::getSettingsValue('moments')) ?? 0) / 100;

        }
        // logger('targetMoment Achieved:', [$per]);

        if ((@$targetReel[0] ?? 0) <= $extras['reel']['upload'] && (@$targetReel[1] ?? 0) <= $extras['reel']['likes'] && (@$targetReel[2] ?? 0) <= $extras['reel']['comments']) {
            $per += (((int) Common::getSettingsValue('reels')) ?? 0) / 100;

        }
        // logger('targetReel Achieved:', [$per]);


        return $per;
    }
}
