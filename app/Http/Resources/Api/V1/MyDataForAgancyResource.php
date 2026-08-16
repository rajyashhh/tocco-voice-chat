<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Family;
use App\Models\Pack;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;

class MyDataForAgancyResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $hasColor = Common::hasInPack(@$this->id, 18, true);

        $data = [
            'id' => @$this->id, // both
            'uuid' => @$this->uuid, // both
            'diamonds' => @$this->monthly_diamond_received ?: 0,
            'name' => @$this->name ?: '', // both
            'phone' => @$this->phone ?? '',
            'country' => $this->country ?? null,
            // 'vip_level' => @$this->UserVip->level,
            'vip' => @Common::ovip_center($this->id), // refactor
            'level' => Common::level_center_min(@$this->id), // refactor
            'profile' => new ProfileForAjancyResource(@$this->profile), // both
            'has_color_name' => false,
            'gender' => $this->gender,
            'colored_name' => (fn($c) => is_string($c) ? $c : '')($hasColor ? common::wareUserVip(@$this->id, 18, 'color') : null),

        ];

        return $data;
    }
}
