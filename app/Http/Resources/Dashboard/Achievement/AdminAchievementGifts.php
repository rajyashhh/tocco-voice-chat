<?php

namespace App\Http\Resources\Dashboard\Achievement;
use App\Helpers\StorageHelper;

use App\Models\Gift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminAchievementGifts extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function get_gift($id) {
        $gift = Gift::find($id);
        if($gift){
            return [
                'id'   =>$gift->id  ?? '',
                'name' =>$gift->name ?? '',
                'img'  =>$gift->img ?? null,
            ];
        }
        else{
            return null;
        }
    }
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

        return [
            'id'         => $this->id,
            'user'      => $this->get_user($this->user_id),
            'gift'      => $this->get_gift($this->gift_id)
        ];
    }
}
