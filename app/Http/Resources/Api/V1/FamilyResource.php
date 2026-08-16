<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\FamilyUser;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = $this->owner;
        if ($user) {
            $owner = [
                'id'        => $user->id,
                'is_family_admin' => @$user->is_family_admin,
                'family_id' => strval($user->family_id),
                'name'  => $user->name,
                'profile' => [
                    'image' => @$user->profile->avatar,
                ],
                'country' => [
                    'id' => @$user->country->id,
                    'name' => @$user->country->name,
                    'flag' => @$user->country->flag,
                ],
                'family_status' => 2,
                'type_user'            => intval(@$user->type_user) ?: 0,
                "manger_type"          => new MangerTypeResource(@$user->mangerType),
                'uuid'                 => @$user->uuid,
                'id_image'             => @$user->specialId?->ware?->show_img ?? '',
                'special_id'          =>  @$user->specialId?->ware?->id ?? 0,
            ];
        } else {
            $owner = new \stdClass();
        }

        // Always use loadMissing to ensure relation is loaded with proper eager loading (fixes N+1)
        $this->resource->loadMissing('allMembers.user');
        $mems = $this->allMembers;

        $authUserId = $request->user()->id;
        $requested = FamilyUser::where('user_id', $authUserId)
        ->where('family_id', $this->id)
        ->where('status', 0)
        ->exists();

        $amIMember = $this->allMembers->where('user_id', $authUserId)->isNotEmpty();

        return [

            'id' => @$this->id,
            'name' => @$this->name ?: '',
            'introduce' => @$this->introduce ?: '',
            'image' => @$this->image ?: '',
            'max_num_of_members' => @$this->num ?: 0,
            'max_num_of_admins' => @$this->num_admins ?: 0,
            'owner' => $owner,
            'am_i_member' => $amIMember,
            'am_i_owner' => (@$this->user_id == $authUserId) ? true : false,
            'am_i_admin' => $request->user()->is_family_admin ? true : false,
            'members' => ShortFamilyUserResource::collection($mems),
            'num_of_requests' => $this->pending_requests_count ?? FamilyUser::query()->where('family_id', $this->id)->where('status', 0)->count(),
            'num_of_members' => ($this->members_count + 1),
            'level' => @$this->level ?: '',
            'requested' => @$requested,
        ];
    }
}
