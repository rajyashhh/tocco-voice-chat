<?php

namespace App\Http\Resources\Dashboard\Invitations;
use App\Helpers\StorageHelper;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInvitationsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        $user = [
            'id'   => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'img'  => $this->profile->avatar,
        ];


        return [
            'user'              => $user,
            'invitations_count' => $this->code_invitations_count,
            'total_earn'        => $this->code_invitations_earn_sum_amount,
            'updated_at'        => $this->code_invitations,
        ];
    }
}
