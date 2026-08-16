<?php

namespace App\Http\Controllers\Dashboard\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthUserController extends Controller
{

    function index(Request $request){
        return $request->user();
    }

    function google_sign_in(Request $request){
        $user = User::where('google_id',$request->google_id)->orWhere('email',$request->email)->first();
        if($user)
        {
            if(!$user->google_id )
            {
                $user->google_id = $request->google_id;
                $user->update();
            }

            $user->tokens()->delete();
            $token = $user->createToken($request->google_id)->plainTextToken;
            return ['status'=>200 , 'token'=>$token ];
        }
        else{
            return response()->json([
                'message'=>'user not found '
            ],404);
        }
    }

    function sign_in(Request $request){
        $request->validate([
            'name' => 'required|exists:admin_users,username',
            'password' => 'required',
        ]);
        $user = Admin::where('username',$request->name)->first();

        if($user && Hash::check($request->password, $user->password))
        {
            // $user->tokens()->delete();
            $token = $user->createToken($request->name)->plainTextToken;
            return ['status'=>200 , 'token'=>$token ];
        }
        else{
            return response()->json( ['status'=>false , 'messages'=>['password'=>'incorrect password'],405]);

        }
    }


}
