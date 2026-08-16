<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Http\Resources\Api\V1\MyDataForAgancyResource;
use App\Models\Agency;
use App\Models\Target;
use App\Models\UserSallary;
use Illuminate\Http\Resources\Json\JsonResource;

class NewAgencyRankingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */


    public function toArray($request)
    {
        $target = UserSallary::where('user_agency_id', $this->ranker?->agency_id)->sum('agency_sallary');

        // Calculate the agency share threshold based on the target

        $minValue = Target::where('usd', '<', $target)
            ->orderBy('usd', 'desc')
            ->first();

        $result = (@$minValue->agency_share / 100) * @$target;

        // $target =Target::where('level',$this->id)->first();
        // // $target_usd =Agency::where('id',$this->id)->sum('target_usd');
        // $Theratio=$target->agency_share /100;
        // $All=$target->usd* $Theratio ;
        /** @var Agency $this*/
        return [
            'exp' => $this->total_gifts,
            'id' => $this->ranker?->id ?: 0,
            'target' => $result ?: 0,
            'name' => $this->ranker?->name ?: '',
            'notice' => $this->ranker?->notice ?: '',
            'phone' => (string) $this->ranker?->phone ?: "",
            'img' => $this->ranker?->img ?: '',
            'owner' => new MyDataForAgancyResource($this->ranker?->owner) ?: [
                "id" => 0,
                "uuid" => '',
                "target_usd" => 0,
                'name' => '',
                "profile" => [
                    "image" => ''
                ]
            ],

        ];
    }
}
