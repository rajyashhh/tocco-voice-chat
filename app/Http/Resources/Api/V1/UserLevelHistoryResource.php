<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use Illuminate\Http\Resources\Json\JsonResource;

class UserLevelHistoryResource extends JsonResource
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
                'uuid' => (int)($this->user?->uuid ?? 0),
                'name' => $this->user?->name ?? '',
                'image' => $this->user?->profile?->avatar ?? '',
            ],
            'admin' => [
               'id' => $this->admin?->id ?? 0,
               'name'=> $this->admin?->name ?? '',
               'image'=> $this->admin?->avatar ?? '',
            ],

           
            'old_total_sender_level' => $this->old_total_sender_level ?? 0,
            'new_total_sender_level' => $this->new_total_sender_level ?? 0,
            'old_total_received_level' => $this->old_total_received_level ?? 0,
            'new_total_received_level' => $this->new_total_received_level ?? 0,
        ];
    }
}
