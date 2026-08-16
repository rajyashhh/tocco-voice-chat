<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // $s_type = 'user';
        // $r_type = 'user';

        // $sender_data = [
        //     'id' => @$this->sender->id ?? 0,
        //     'uuid' => @$this->sender->uuid ?: 0,
        //     'name' => @$this->sender->name ?? "",
        //     'img' => @$this->sender->img ?? "",
        //     'type' => $s_type
        // ];
        // $receiver_data = [
        //     'id' => @$this->receiver->id ?? 0,
        //     'uuid' => @$this->receiver->uuid ?: 0,
        //     'name' => @$this->receiver->name ?? "",
        //     'img' => @$this->receiver->img ?? "",
        //     'type' => $r_type
        // ];



        return [
            'id' => $this->id,
            // 'sender' => $sender_data,
            // 'receiver' => $receiver_data,
            'sender' => Common::getChargerInfo($this),
            'receiver' =>  Common::getReceiverInfo($this),
            'value' => $this->amount,
            'usd' => $this->usd,
            'time' => Carbon::parse($this->created_at)->format('Y-m-d h:i:s A')
        ];
    }
}
