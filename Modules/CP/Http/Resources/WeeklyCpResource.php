<?php

namespace Modules\CP\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklyCpResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */

     public $data;

     // Modify the constructor to accept gift IDs
     public function __construct($resource, $data )
     {
         parent::__construct($resource);
         $this->data = $data;
     }
    public function toArray($request)
    {
        $endDate = $this->end_date_local;
        $time = Carbon::now()->diff($endDate);
        $timeComponents = [
            'day' => @$time->days ?? 0,
            'hour' => @$time->h ?? 0,
            'minute' => @$time->i ?? 0,
            'second' => @$time->s ?? 0,
        ];
        return [
            'remainingTime' => $timeComponents,
            'description' =>$this->data != null ? app()->getLocale() == 'ar' ? $this->data->desc_ar : $this->data->desc_en : "",
            'gifts' => WeeklyCpGiftsResource::collection($this->gifts),
            'top_first_rewards' => WeeklyCpRewardsResource::collection($this->weeklyCpGifts->where("level", 1)),
            'top_second_rewards' => WeeklyCpRewardsResource::collection($this->weeklyCpGifts->where("level", 2)),
            'top_third_rewards' => WeeklyCpRewardsResource::collection($this->weeklyCpGifts->where("level", 3)),

        ];
    }
}
