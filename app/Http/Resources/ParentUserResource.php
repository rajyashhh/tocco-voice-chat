<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentUserResource extends JsonResource
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
            'uuid' => $this->uuid,
            'name' => $this->name,
            'codeInvitations' => $this->codeInvitations->count(),
            'codeInvitationsEarn' => $this->codeInvitationsEarn->sum('amount'),
            'created_at' => $this->created_at
        ];
    }
}


