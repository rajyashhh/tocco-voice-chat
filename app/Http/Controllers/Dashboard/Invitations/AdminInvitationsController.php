<?php

namespace App\Http\Controllers\Dashboard\Invitations;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Invitations\AdminInvitationsResource;
use App\Http\Resources\Dashboard\Invitations\MiniAdminInvitationsResource;
use App\Models\User;
use App\Models\UserCodeInvitation;
use App\Models\UserEarnInvitation;
use Illuminate\Http\Request;

class AdminInvitationsController extends Controller
{

    public function index()
    {
        $users = User::whereHas('codeInvitations')->with('codeInvitations')->withCount('codeInvitations')->withSum('codeInvitationsEarn','amount')->get();
        return AdminInvitationsResource::collection($users);
    }

    public function show(string $id)
    {
        $userId = $id;
        $invitations = UserCodeInvitation::orderByDesc('created_at')
        ->with('invited')
        ->where('user_id', $userId)
        ->get()
        ->pluck('invited')
        ->filter()
        ->pluck('id')->toArray();
        $data = [];
       for ($i=0; $i < count($invitations); $i++) {
            $user = User::find($invitations[$i]);
            if($user){
                $parent_percentage = UserEarnInvitation::where('user_id', $invitations)->where('parent_id', $userId)->get();
                $data [] = [
                    'parent_percentage' => $parent_percentage->sum('amount'),
                    'user'=>[
                        'id'                  => $user->id,
                        'uuid'                => $user->uuid,
                        'name'                => $user->name,
                        'img'                 => @$user->profile->avatar,
                    ]
                ];
            }
       }
        return $data;
    }

    public function Operations(string $id , $user_id)
    {
        $users = UserEarnInvitation::where('user_id',$user_id)->where('parent_id',$id)->get();
        return  MiniAdminInvitationsResource::collection( $users);
    }
}
