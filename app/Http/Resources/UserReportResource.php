<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserReportResource extends JsonResource
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
            'name' => @$this->name ?: '',
            'diamond' => $this->total_diamonds,
            'target' => $this->total_salary,
            'expenses' => $this->total_cut_amount,
            'salary'   => $this->final_salary,
            'agency_name' => $this->agency->name ?? '',
        ];
    }
}
