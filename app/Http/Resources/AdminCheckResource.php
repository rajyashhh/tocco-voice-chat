<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCheckResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'request' => [
                'agency_id'   => $this->request->agency_id,
                'agency_owner_id' => $this->request->agency_owner_id,
                'host_id' => $this->request->host_id,
                'status' => $this->request->status,
                'usd' => $this->request->usd,
                'coins' => $this->request->coins,
                'host_check' => $this->request->host_check == 1 ? __('Accept') : ($this->request->host_check == 2 ? __('Reject') : __('Pending')),
                'bill_image' => $this->request->bill_image ?? '',
            ],
            'Shipping_agent_id' => $this->request?->agency?->owner?->uuid ?? 0,
            'host' => [
                'id' =>   $this->request?->host?->uuid ?? 0,
                'name' => $this->request?->host?->name ?? '',
            ],

        ];
    }
}
