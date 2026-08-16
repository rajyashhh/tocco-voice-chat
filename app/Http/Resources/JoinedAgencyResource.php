<?php

namespace App\Http\Resources;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JoinedAgencyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'join_date'  => $this->join_date ?? '',
            'leave_date' => $this->leave_date ?? '',
            'agency_id'  => $this->agency_id ?? 0,
            'img' => $this->agency->img ?? '', 
        ];
    
    }
}
