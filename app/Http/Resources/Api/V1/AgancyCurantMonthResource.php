<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class AgancyCurantMonthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $details = Common::CurantUsdHistoryOwner($this->id, $this->month, $this->year);


        return [
            'id' => $this->id,
            'uuid'=>@$this->uuid, // both
            'diamonds' => $this->monthly_diamond_received,
            'name' => $this->name,
            'profile'=>new ProfileForAjancyResource(@$this->profile), // both
            // 'usd'=>@(double)$this->userSallary->agency_sallary??0, // both
            'total_used'=>$details??0, // both
            'has_color_name'=>Common::hasInPack ($this->id,18,true), // both
            'id_image'             => @$this->specialId?->ware?->show_img ?? '',
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,





        ];
    }
}
