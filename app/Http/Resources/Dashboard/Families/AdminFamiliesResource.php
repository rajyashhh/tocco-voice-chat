<?php

namespace App\Http\Resources\Dashboard\Families;
use App\Helpers\StorageHelper;

use App\Models\FamilyLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminFamiliesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function get_user($id ){
        $user = User::withTrashed()->find($id);
        if($user)
        {
            return [
                'id'   =>$user->id  ?? '',
                'name' =>$user->name ?? '',
                'img'  =>$user->profile->avatar ?? null,
            ];
        }
        else{
            return null;
        }
    }

    public function toArray(Request $request): array
    {
        $level = FamilyLevel::find($this->current_level_id);
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'status'     => $this->status,
            'users_num'  => $this->num,
            'introduce'  => $this->introduce,
            'notice'     => $this->notice,
            'img'        => $this->image,
            'level'      => $level ? $level->name : null,
            'owner'      => $this->get_user($this->user_id)
        ];
    }
}
