<?php

namespace App\Http\Resources\Dashboard\Posts;
use App\Helpers\StorageHelper;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminMomentResource extends JsonResource
{
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
            'id'           => $this->id,
            'description'  => $this->description,
            'img'          => empty($this->img) ? null: $this->img ,
            'likes'        => $this->likes->count(),
            'comments'     => $this->comments->count(),
            'user'         => $this->get_user($this->user_id),
            'created_at'   => Carbon::parse($this->created_at)->format('Y-m-d H:i:s A'),
        ];
    }
}
