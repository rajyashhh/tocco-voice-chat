<?php

namespace Modules\AgencyApp\Transformers;

use App\Helpers\Common;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class AgancyHostReportResource extends JsonResource
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
            'image'=>@$this->profile?->avatar,
            'name' => $this->name,
//            'profile'=>new ProfileForAjancyResource(@$this->profile), // both
            // 'usd'=>@(double)$this->userSallary->agency_sallary??0, // both
            'total_used'=>$this->userSalary?->sallary??0, // both
//            'has_color_name'=>Common::hasInPack ($this->id,18,true), // both
            'diamonds' => $this->month_diamond,
            'gender' => $this->gender,
        ];
    }
}
