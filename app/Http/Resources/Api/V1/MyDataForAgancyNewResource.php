<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Family;
use App\Models\Pack;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;

class MyDataForAgancyNewResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $data = [
            'id' => @$this->id, // both
            'uuid' => @$this->uuid, // both
            // 'diamonds' => @$this->monthly_diamond_received ?: 0,

            'name' => @$this->name ?: '', // both
            // 'vip_level' => @$this->UserVip->level,
            // 'vip'=>@Common::ovip_center ($this->id), // refactor
            // 'level'=>Common::level_center_min (@$this->id), // refactor

            'image' => @$this->profile->avatar ??'', // both
            // 'has_color_name'=>Common::hasInPack ($this->id,18,true),
            // 'gender'=>$this->gender,
        ];

        return $data;
    }
}
