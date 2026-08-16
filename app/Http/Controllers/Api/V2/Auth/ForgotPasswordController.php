<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Models\Code;
use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Helpers\FirebaseValidate;
use App\Http\Controllers\Controller;
use App\Http\Services\OtpProviderService;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class ForgotPasswordController extends Controller
{
    public function reset(Request $request){
        $otpProvider = new OtpProviderService();

        if ($otpProvider->usesServerCode()) {
            if (!$request->phone || !$request->password || !$request->code) return Common::apiResponse(0, 'missing params');

            if (!$otpProvider->verify($request->phone, (string) $request->code)) {
                return Common::apiResponse(false, __('api_responses.invalid_code'));
            }
        } else {
            if (!$request->phone || !$request->password || !$request->firebase_id_token) return Common::apiResponse(0, 'missing params');

            try {
                FirebaseValidate::validateIdToken($request->firebase_id_token);
            } catch (\Throwable $e) {
                return Common::apiResponse(false, __('api_responses.invalid_code'));
            }
        }
        $user = User::query ()->where ('phone',$request->phone)->first ();
        if (!$user)  return Common::apiResponse(0, 'validate your phone', null, 422);
        $user->password = $request->password;
        $user->save();

        if ($otpProvider->usesServerCode()) {
            $otpProvider->consume($request->phone);
        }

        return Common::apiResponse (1,'reset successful',null);
    }

    public function verifyCode(Request $request){
        $otpProvider = new OtpProviderService();

        if ($otpProvider->usesServerCode()) {
            if (!$request->phone || !$request->code) return Common::apiResponse(0, 'missing params');

            if (!$otpProvider->verify($request->phone, (string) $request->code)) {
                return Common::apiResponse(false, __('api_responses.invalid_code'));
            }

            return Common::apiResponse (1,'valid code',null);
        }

        if (!$request->phone ||  !$request->firebase_id_token) return Common::apiResponse(0, 'missing params');

        try {
            FirebaseValidate::validateIdToken($request->firebase_id_token);
        } catch (\Throwable $e) {
            return Common::apiResponse(false, __('api_responses.invalid_code'));
        }

        return Common::apiResponse (1,'valid code',null);
    }
}