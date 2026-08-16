<?php

namespace Modules\Events\Transformers;

use App\Models\Target;
use Carbon\Carbon;
use App\Helpers\Common;

use Modules\Events\Entities\GeneralRole;
use Illuminate\Http\Resources\Json\JsonResource;

class UserChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */


    public function toArray($request)
    {

        $currentDate = Carbon::now();
        $endOfMonth = $currentDate->copy()->endOfMonth();

        $timeDifference = $currentDate->diff($endOfMonth);

        $timeComponents = [
            'day'    => $timeDifference->d,
            'hour'   => $timeDifference->h,
            'minute' => $timeDifference->i,
            'second' => $timeDifference->s,
        ];
        $TotalAmount  = $this->charges->sum('amount') + $this->coinLogs->sum('obtained_coins');
        $rule = GeneralRole::query()->where("type", 'charge_event')->first();
        if ($TotalAmount < 0 ){
            $TotalAmount = 0 ;
        }
        $next_target = Target::where('diamonds', '>', $this->monthly_diamond_received)->orderBy('diamonds')->first();

        return [
            'user' => [
                'user_id'   => $this->id,
                'uuid'      => $this->uuid ?? 0,
                'name'      => $this->name ?? '',
                'avatar'    => $this->profile?->avatar ?? '',
                'amount'    => $TotalAmount ?? 0,
                'next_target' => $next_target->diamonds ?? $this->monthly_diamond_received ,
                'remaining' => $this->monthly_diamond_received
            ],

            'role'      => $this->localizedDescription($rule),
            'remainingTime' => $timeComponents,
        ];
    }

    private function localizedDescription(?GeneralRole $rule): string
    {
        if ($rule == null) {
            return '';
        }

        $locale = app()->getLocale();
        $column = in_array($locale, ['ar', 'tr', 'hi']) ? "desc_{$locale}" : 'desc_en';

        return $rule->{$column} ?? $rule->desc_en ?? '';
    }
}
