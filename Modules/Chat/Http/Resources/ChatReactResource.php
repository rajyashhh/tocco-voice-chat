<?php

namespace Modules\Chat\Http\Resources;

use App\Helpers\UserCommon;
use App\Models\User;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\MessageAlbum;
use Modules\Chat\Entities\MessageReplay;
use Modules\Chat\Entities\React;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class ChatReactResource extends JsonResource
{
    function get_user($id) {
        $user = User::where('id',$id)->first();
        if($user)
        {
            return [
                'id'   =>$user->id ,
                'uuid'   =>$user->uuid ,
                'name' =>$user->name ?? '',
                'img'  =>$user->profile?->avatar ?? null,
            ];
        }
        else{
            return null;
        }
    }


    public function toArray(Request $request)
    {

        return [
            'id'                  => $this->id,
            'user_id'             => $this->user_id,
            'react'               => $this->react,
            'user'                =>$this->get_user($this->user_id),
        ];
    }
}

