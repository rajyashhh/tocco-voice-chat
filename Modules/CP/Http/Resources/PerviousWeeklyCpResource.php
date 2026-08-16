<?php

namespace Modules\CP\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PerviousWeeklyCpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' =>  Carbon::parse($this->end_date)->format('Y-m-d') ?? '',
            'top_there' => PerviousWeeklyCpUsersDetailsResource::collection($this->WeeklyCpWinners),
        ];
    }
}
