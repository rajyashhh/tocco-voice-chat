<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class UserTargetResource extends JsonResource
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
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id ?? 0,
                'name' => $this->user?->name ?? '',
                'uuid' => $this->user?->uuid,
                'image' => $this->user?->profile?->avatar ?? '',
                'country' => $this->user?->country?->flag ?? '',
                'coins' => number_format($this->user?->di ?? 0),
                'email' => $this->user?->email,
                'gold' => $this->user?->gold,
                'is_host' => $this->user?->is_host,
            ],
            'agency' => [
                'id' => @$this->agency_id ?? 0,
                'name' => $this->agency->name ?? '',
                'phone' => $this->agency->phone ?? '',
                'img' => $this->agency->img ?? '',
                'contents' => $this->agency->contents ?? '',
            ],
            'target_id' => $this->target_id,
            'target_diamonds' => $this->target_diamonds,
            'add_month' => $this->add_month,
            'add_year' => $this->add_year,
            'target_usd' => $this->target_usd,
            'target_hours' => $this->target_hours,
            'target_days' => $this->target_days,
            'target_agency_share' => $this->target_agency_share,
            'user_diamonds' => $this->user_diamonds,
            'user_hours' => $this->user_hours,
            'user_days' => $this->user_days,
            'user_obtain' => $this->user_obtain,
            'agency_obtain' => $this->agency_obtain,

        ];
    }
}
