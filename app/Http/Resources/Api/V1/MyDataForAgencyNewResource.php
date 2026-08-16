<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Family;
use App\Models\Pack;
use App\Models\Room;
use Illuminate\Http\Resources\Json\JsonResource;

class MyDataForAgencyNewResource extends JsonResource
{
    public $type;

    public function __construct($resource, $type = 'default')
    {
        parent::__construct($resource);
        $this->type = $type;
    }

    // Override the default collection method
    public static function collection($resources, $type = 'default')
    {
        return $resources->map(function ($resource) use ($type) {
            return new static($resource, $type);
        });
    }

    public function toArray($request)
    {
        $data = [
            'id' => @$this->user->id, // both

            'uuid' => @$this->user->uuid, // both
            'diamonds' => @$this->user->monthly_diamond_received ?: 0,
            'phone' => @$this->user->phone ?? '',
            'country' => $this->user->country ?? null,
            'name' => @$this->user->name ?: '', // both
            // 'vip_level' => @$this->UserVip->level,
            'vip'=>@Common::ovip_center ($this->user->id), // refactor
            'level'=>Common::level_center_min (@$this->user->id), // refactor

             'profile' => new ProfileForAjancyResource(@$this->user->profile), // both
            // 'has_color_name'=>Common::hasInPack ($this->user->id,18,true),
            'status' => $this->status,
            'type' => $this->type,
        ];
        if ($this->status !=0){
            $operator_name='';
            if ($this->change_status_type == "app") {
                $operator_name = $this->userOperator?->name;
            }else{
                $operator_name = $this->admin?->name;
            }
            $data['operator'] = $operator_name;
            $data['date'] = $this->updated_at;
        }

        return $data;
    }
}
