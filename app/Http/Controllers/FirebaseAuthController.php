<?php

namespace App\Http\Controllers;


use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Services\FirebaseAuthService;
use Illuminate\Support\Facades\Log;



class FirebaseAuthController extends Controller
{

    protected FirebaseAuthService $firebase;

    public function __construct(FirebaseAuthService $firebase)
    {
        $this->firebase = $firebase;
    }


    public function loginWithUid(Request $request)
    {
        $user = Auth::user();
        if (!$user->firebase_uuid){
            $data = $this->firebase->createGuest();

            $firebase_uid = $data['uid'];
            $firebase_token = $data['token'];
             $data = ['firebase_token' => $firebase_token];
            return Common::apiResponse(1, 'done',  $data, 200);
        }

        $customToken = $this->firebase->createCustomToken(Auth::user()->firebase_uuid);
        $data = ['firebase_token' => $customToken,];
        return Common::apiResponse(1, 'done',  $data, 200);
        // return response()->json([
        //     'firebase_token' => $customToken,
        // ]);
    }
}
