<?php

namespace App\Http\Resources\Dashboard\Room;
use App\Helpers\StorageHelper;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRoomsResource extends JsonResource
{



    function get_user($id , $key = null){
        $user = User::withTrashed()->find($id);
        if($user)
        {
            return [
                'id'   =>$user->id  ?? '',
                'name' =>$user->name ?? '',
                'img'  =>$user->profile->avatar ?? null,
                'key' =>$key,
            ];
        }
        else{
            return null;
        }
    }

    function users_mic($data) {
        $users = explode(',', $data);
        $users_array = [];
        foreach ($users as $key=>$user) {
            if($user > 0)
            {
                $check_user = $this->get_user($user);
                if($check_user !== null )
                {
                    $users_array[] = $this->get_user($user , $key);
                }
            }
        }
        return $users_array;
    }

    public function toArray(Request $request): array
    {
        return[
            'id'                => $this->id,
            'owner'             => $this->get_user($this->uid),
            'room_status'       => (int)$this->room_status,
            'top_room'          => $this->top_room,
            'pin'               => $this->pin,
            'max_admin'         => $this->max_admin,
            'admins'            =>$this->users_mic($this->room_admin),
            'name'              => $this->room_name,
            'cover'             => $this->room_cover,
            'users_count'       => $this->count_room_socket,
            'room_pass'       => $this->room_pass,
            'microphones'       => $this->users_mic($this->microphone),
            'microphones_count' => count($this->users_mic($this->microphone)),
        ];
    }
}
