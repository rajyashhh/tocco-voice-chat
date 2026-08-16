<?php

namespace App\Http\Controllers\Dashboard\Trash;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Trash\TrashUserResource;
use App\Models\User;
use Illuminate\Http\Request;

class AdminTrashController extends Controller
{

    public function index(Request $request)
    {
        $data= null;
        if($request->type == 'users')
        {
          $data = User::onlyTrashed()->orderBy('id','desc')->with('profile')->get();
        }
        return TrashUserResource::collection($data);
    }

    public function update(Request $request, string $id)
    {
        if($request->type == 'users')
        {
            $User = User::withTrashed()->find($id);
            $User->restore();
        }
        return 200;
    }

    public function destroy(Request $request ,string $id)
    {
        $type = $request->type;
        if($type == 'users')
        {
            $User = User::withTrashed()->find($id);
            $User->forceDelete();
        }
        return 200;
    }
}
