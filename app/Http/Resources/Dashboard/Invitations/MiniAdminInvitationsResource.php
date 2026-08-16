<?php

namespace App\Http\Resources\Dashboard\Invitations;
use App\Helpers\StorageHelper;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniAdminInvitationsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = User::withTrashed()->find($this->user_id);
        if($user)
        {
            $user = [
                'id'                  => $user->id,
                'uuid'                => $user->uuid,
                'name'                => $user->name,
                'img'                 => @$user->profile->avatar,
            ];
        }
        else{
            $user = null;
        }


        return [
            'user'              => $user ,
            'parent_percentage' => $this->parent_percentage,
            'user_charge'       => $this->user_charge,
        ];
    }
}
