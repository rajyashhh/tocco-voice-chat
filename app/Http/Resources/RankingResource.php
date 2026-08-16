<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'exp' => numToString(ceil($this->exp)),
            'exp_diff' => $this->exp_diff,
            'exp_int' => $this->exp_int,
            'remaining' => $this->remaining,
            'remaining_int' => $this->remaining_int,
            $this->merge(new RankingUserResource($this->user))
        ];
    }
}
