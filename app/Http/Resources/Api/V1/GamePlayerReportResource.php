<?php

namespace App\Http\Resources\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Resources\Json\JsonResource;

class GamePlayerReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => @$this->name,
            'total_coins_win' => (int)@$this->coinGameUser[0]?->total_coins_win ?? 0,
            'total_coins_lose' =>@$this->coinGameUser[0]?->total_coins_lose ?? 0,
        ];
    }
}
