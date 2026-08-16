<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\ParentUserResource;
use App\Models\User;
use App\Models\UserCodeInvitation;
use Illuminate\Http\Request;

class ParentUsersController extends Controller
{
    public function index()
    {

        $per_page = request('per_page') ?? 10;
        $search = request('search');
        $users = User::has('codeInvitations')
        ->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
                ->orWhere('uuid', $search);
        })->paginate($per_page);

        return Common::apiResponse(1, 'success', ParentUserResource::collection($users), 200);
    }


    public function users($id)
    {

        $per_page = request('per_page') ?? 10;
        $search = request('search');
        $ids = UserCodeInvitation::where('user_id', '=', $id)->pluck('invited_id')->toArray();
        $users = User::where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%$search%")
                ->orWhere('id', $search)
                ->orWhere('uuid', $search);
        })
        ->where('id', '!=', $id)
        ->whereIn('id', $ids)
        ->paginate($per_page);

        return Common::apiResponse(1, 'success', ParentUserResource::collection($users), 200);
    }
}
