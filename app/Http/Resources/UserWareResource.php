<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWareResource extends JsonResource
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
            'special_id' => $this->ware->value,
            'user' => $this->user->name,
            'user id' => $this->user->uuid,
            'status' => $this->disable ? __('Enabled') : __('Disabled')
        ];
    }
}
