<?php

namespace App\Http\Resources\Dashboard\Bans;
use App\Helpers\StorageHelper;

use App\Models\BanType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BansResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function get_user($uuid) {
        $user = User::where('uuid',$uuid)->first();
        if($user)
        {
            return [
                'id'   =>$user->id ,
                'uuid'   =>$user->uuid ,
                'name' =>$user->name ?? '',
                'img'  =>$user->profile->avatar ?? null,
            ];
        }
        else{
            return null;
        }
    }
    function type($id) {
        $ban = BanType::find($id);
        if($ban)
        {
            return [
                'id' =>$ban->id ?? null,
                'name_ar' =>$ban->name_ar ?? null,
                'name_en'   =>$ban->name_en ?? null,
            ];
        }
        else{
            return [
                'id' => null,
                'name_ar' => null,
                'name_en'   => null,
            ];
        }
    }
    public function toArray(Request $request): array
    {
        return[
            'id' =>$this->id,
            'user' =>$this->get_user($this->uid),
            'ban' =>$this->type($this->ban_type_id),
            'type' =>$this->type,
            'img' =>$this->img,
            'duration' =>$this->duration,
            'device_number' =>$this->device_number,
            'description_ar' =>$this->description_ar,
            'description_en' =>$this->description_en,
        ];
    }
}
