<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingGameCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

            $data = collect($this->resource);
    
            return [
                'user'  => $this->user ?? new \stdClass(),
                'top'   => RankingGameResource::collection($data->take(3)),
                'other' => RankingGameResource::collection($data->slice(3)->values()),
            ];
        
    }
}
