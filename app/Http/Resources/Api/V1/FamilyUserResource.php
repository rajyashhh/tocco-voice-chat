<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Family;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $this->status = $this->status?:0;
        $statuses = [
            0=>'pending',
            1=>'accepted',
            2=>'denied'
        ];
        $types = [
            'normal'=>0,
            'admin'=>1,
        ];
        return [
            'id'=>$this->id,
            'user'=> new UserResource(User::query ()->find ($this->user_id)),
            'family'=>new FamilyResource(Family::query ()->find ($this->family_id)),
            'status'=>$statuses[$this->status],
            'time'=>\Carbon\Carbon::parse($this->created_at)->diffForHumans(),
            'types'=>$types
        ];
    }
}
