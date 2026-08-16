<?php

namespace App\Http\Resources\Api\V2;
use App\Helpers\StorageHelper;

use App\Http\Resources\Api\V1\MangerTypeResource;
use App\Http\Resources\Api\V1\ShortFamilyUserResource;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{

    public function toArray($request)
    {
        $currentUser  = $request->user();
        $userId = @$currentUser->id;


        return [
            'id'                 => @$this->id,
            'name'               => @$this->name ?: '',
            'introduce'          => @$this->introduce ?: '',
            'image'              => @$this->image ?: '',
            'max_num_of_members' => @$this->num ?: 0,
            'max_num_of_admins'  => @$this->num_admins ?: 0,
            'owner'              => $this->owner ? new ShortFamilyUserResource($this->owner) : new \stdClass(),
            'am_i_member'        => $this->amMember($userId),
            'am_i_owner'         => $this->amOwner($userId) ,
            'am_i_admin'         => (bool)$this->id == @$currentUser->family_id && @$currentUser->is_family_admin,
            'members'            => ShortFamilyUserResource::collection($this->members),
            'num_of_requests'    => $this->users_requests_count,
            'num_of_members'     => $this->members_count,
            'level'              => @$this->level ?: '',

        ];
    }
}
