<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class PaymentCoinResource extends JsonResource
{
    public function toArray($request)
    {
     

        return [
            'id' => $this->id,
            'title' => $this->title,
            'photo' => $this->photo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => $this->status,
            'type' => $this->type,
            'package_type' => $this->package_type,
            'description' => $this->description,
        'coins' => CoinResource::collection($this->whenLoaded('coinsV2')),
        ];
    }
}
