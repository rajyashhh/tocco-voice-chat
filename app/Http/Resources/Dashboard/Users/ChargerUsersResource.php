<?php

namespace App\Http\Resources\Dashboard\Users;
use App\Helpers\StorageHelper;

use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChargerUsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $send_count = Charge::where('charger_id',$this->id)->count();
        $received_count = Charge::where('user_id',$this->id)->count();
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'agency_id' => $this->agency_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'coins' => $this->coins,
            'image' =>  $this->image,
            'send_count' =>  $send_count,
            'received_count' =>  $received_count,
        ];
    }
}
