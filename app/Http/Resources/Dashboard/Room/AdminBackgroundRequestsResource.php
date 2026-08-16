<?php

namespace App\Http\Resources\Dashboard\Room;
use App\Helpers\StorageHelper;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBackgroundRequestsResource extends JsonResource
{

    function get_user($id){
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

    function types($type){
        $types = [
            0=>'pending',
            1=>'accepted',
            2=>'denied'
        ];
        return $types[$type] ?? null;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'img'      => $this->img,
            'status'   => $this->types($this->status),
            'owner'    => $this->get_user($this->owner_room_id),
        ];
    }
}
