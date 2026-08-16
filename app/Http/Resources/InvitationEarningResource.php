<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationEarningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'parent_id'         => $this->parent_id,
            'user_id'           => $this->user_id,
            'user_charge'       => $this->user_charge,
            'parent_percentage' => $this->parent_percentage,
            'source_type'       => $this->source_type,
            'amount'            => $this->amount,
            'charge_id'         => $this->charge_id,
            'is_claimed'        => $this->is_claimed,
            'created_at'        => $this->created_at,
        ];
    }
}
