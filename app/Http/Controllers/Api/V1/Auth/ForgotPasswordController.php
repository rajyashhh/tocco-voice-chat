<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Helpers\Common;
use App\Helpers\FirebaseValidate;
use App\Http\Controllers\Controller;
use App\Http\Services\WhatsappWebhook;
use App\Models\Code;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class ForgotPasswordController extends Controller
{
    public function reset(Request $request){
        if (!$request->phone || !$request->password || !$request->firebase_id_token) return Common::apiResponse(0, 'missing params');
        try {
            FirebaseValidate::validateIdToken($request->firebase_id_token);
        } catch (\Throwable $e) {
            return Common::apiResponse(false, __('api_responses.invalid_code'));
        }
        $user = User::query ()->where ('phone',$request->phone)->first ();
        $user->password = $request->password;
        $user->save();
        return Common::apiResponse (1,'reset successful',null);
    }

    public function verifyCode(Request $request){
        if (!$request->phone ||  !$request->firebase_id_token) return Common::apiResponse(0, 'missing params');

        try {
            FirebaseValidate::validateIdToken($request->firebase_id_token);
        } catch (\Throwable $e) {
            return Common::apiResponse(false, __('api_responses.invalid_code'));
        }

        return Common::apiResponse (1,'valid code',null);
    }


    public function resetWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook){
        if (!$request->phone || !$request->password) return Common::apiResponse(0, 'missing params');
        $phone = $request->phone;

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate){
            return Common::apiResponse(false, __('current phone not verified'));
        }

        $user = User::query ()->where ('phone',$request->phone)->first ();
        $user->password = $request->password;
        $user->save();
        return Common::apiResponse (1,'reset successful',null);
    }
}
