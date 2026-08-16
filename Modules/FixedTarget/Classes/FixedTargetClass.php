<?php

namespace Modules\FixedTarget\Classes;

use App\Helpers\Common;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\FixedTarget\Entities\FixedTarget ;
use Modules\FixedTarget\Interfaces\TargetInterface;

class FixedTargetClass implements TargetInterface
{

    public function getTarget(int $diamond): Model|null
    {
        return FixedTarget::query()->where('diamonds', '<=', $diamond)->orderBy('diamonds', 'desc')->first();
    }

    public function calculateUsdFromTarget(Model $target, float $hours, int $days, array $extra = null): float
    {
       
        $per = 0.50;
        if ($target->hours <= $hours) {
            $per += 0.20;
        }
        if ($target->days <= $days) {
            $per += 0.30;
        }
        if (Common::getConf('all_target_or_nothing') == 'true') {
            if ($per < 1) {
                $per = 0;
            }
        }

        
        return $target->usd * $per;
    }

    public function findClosestElement($input)
    {
        $data = Cache::remember('fixed_target', now()->addMinutes(1), function () {
            return FixedTarget::all()->sortBy('usd');
        });

        $closestElement = null;
        $minDistance    = PHP_INT_MAX;
        $currentId      = null;
        $closestId      = null;

        foreach ($data as $element) {
            $distance    = 0;
            $isAllTarget = true;
            foreach ($element->getAttributes() as $key => $value) {
                if (!in_array($key, [
                    "diamonds",
                    "hours",
                    "days",
                    "count_moment",
                    "count_real",
                ])) {
                    continue;
                }
                if ($key !== 'id' && $input[$key] < $value) {
                    $isAllTarget = false;
                }

                if ($key !== 'id') {
                    $distance += pow($input[$key] - $value, 2);
                }
            }

            if ($isAllTarget) {
                $currentId = $element['id'];

            }

            $distance = sqrt($distance);

            if ($distance < $minDistance) {
                $minDistance    = $distance;
                $closestElement = $element;
                $closestId      = $element->id;
            }

        }

        return [
            'currentElement'      => $data->where('id', $currentId)->first(),
            'closestId'      => $closestId,
            'closestElement' => $closestElement,
        ];
    }

    public function calculatePercentageAchieved(Model $target, float $hours, int $days, array $extra = null): float
    {
       
        $per = 0.50;
        if ($target->hours <= $hours) {
            $per += 0.20;
        }
        if ($target->days <= $days) {
            $per += 0.30;
        }
        if (Common::getConf('all_target_or_nothing') == 'true') {
            if ($per < 1) {
                $per = 0;
            }
        }

        
        return  $per;
    }
}
