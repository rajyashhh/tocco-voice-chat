<?php

namespace Modules\TribeReward\Transformers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TribePeriodResource extends JsonResource
{
    public function toArray($request)
    {
        $rewards = [];
        foreach ($this->tribeTops as $top) {
            $range = ($top->min == $top->max)
                ? "top {$top->min}"
                : "top {$top->min}-{$top->max}";

            $rewards[$range] = TribeRewardResource::collection($top->tribeRewards);
        }

        $currentDate = Carbon::parse($this->start_date);
        $endAt = Carbon::parse($this->end_date);

        $timeDifference = $currentDate->diff($endAt);

        $timeComponents = [
            'day'    => $timeDifference->d,
            'hour'   => $timeDifference->h,
            'minute' => $timeDifference->i,
            'second' => $timeDifference->s,
        ];

        return [
            'role' => 'Top Agency Leader',
            'end_time' => $this->end_date,
            'rewards' => !empty($rewards) ? $rewards : (object)[],
            'remainingTime' => $timeComponents,
        ];
    }
}
