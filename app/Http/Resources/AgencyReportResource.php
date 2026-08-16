<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyReportResource extends JsonResource
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
            'name' => $this?->name ?? '',
            'target' => $this->target,
            'expenses' => $this->expenses,
            'salary' => $this->salary,
            'agent' => $this->owner?->name ?? $this->dashOwner?->name,
            'users' => $this->users_count,
        ];
    }
}
