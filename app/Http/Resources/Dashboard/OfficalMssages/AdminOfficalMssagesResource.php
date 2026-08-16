<?php

namespace App\Http\Resources\Dashboard\OfficalMssages;
use App\Helpers\StorageHelper;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOfficalMssagesResource extends JsonResource
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
        return [
            'id' => $this->id,
            'img' => $this->img,
            'title' => $this->title,
            'user_id' => $this->user_id,
            'url' => $this->url,
            'content' => $this->content,
            'user' => $this->get_user($this->user_id)
        ];
    }
}
