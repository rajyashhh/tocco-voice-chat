<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargeRecievedInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $sender  =  $this->sender;
        $s_type = 'user';


        $sender_data = [
            'id'=>@$sender->id??0,
            'uuid'=>@$sender->uuid?:0,
            'name'=>@$sender->name??"",
            'img'=>@$sender->img??"",
            'type'=>$s_type
        ];
        return [
            'id'=>$this->id,
            'sender'=>$sender_data,
            'value'=>$this->amount,
            'usd'=> $this->usd,
            'time'=>Carbon::parse($this->created_at)->format('Y-m-d h:i:s A')
        ];
    }
}
