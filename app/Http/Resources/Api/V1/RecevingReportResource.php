<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class RecevingReportResource extends JsonResource
{

    public function toArray($request)
    {
        $user = auth()->user();
        // if ($this->charger_type == 'dash') {
        //     $name = $this->admin->name ?? '';
        //     $image = $this->admin->avatar ?? '';
        //     $uuid = $this->sender->uuid ?? '';
        // } elseif($this->charger_type == 'agency') {
        //     $name = $this->senderAll->name ?? '';
        //     $image = $this->senderAll->img ?? '';
        //     $uuid = $this->senderAll->id ?? '';
        // } else {
        //     $name = $this->sender->name ?? '';
        //     $image = $this->sender->profile->avatar ?? '';
        //     $uuid = $this->sender->uuid ?? '';
        // }
        $charger = Common::getChargerInfo($this);
        $value = common::wareUserVipV2($charger['id'], 18, 'color');
        return [
            'id'            => $this->user_id,
            'uuid'          => $charger['uuid'],
            'diamonds'      => numToStringNew($this->amount),
            'operation_no'  => (int)$this->id,
            'created_at'    => Carbon::parse($this->created_at)->format('Y-m-d h:i:s A'),
            'name'          => $charger['name'] ?? '',
            'image'         => $charger['image'] ?? '',
             'color_name' => is_string($value) ? $value :  '',


        ];
    }
}
