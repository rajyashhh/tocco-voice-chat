<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class CoinGameUserReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->game?->name,
            'img' => "",
            'value' =>  ( $this->type == 1 ? $this->coins : - $this->coins),
            'created_at' => $this->created_at,
        ];
    }
}
