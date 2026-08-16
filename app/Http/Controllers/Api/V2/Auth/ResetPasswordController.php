<?php

namespace App\Http\Controllers\Api\V2\Auth;

use App\Helpers\Common;
use App\Helpers\FirebaseValidate;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Services\OtpProviderService;
use App\Http\Services\WhatsappWebhook;
use App\Models\Code;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        $otpProvider = new OtpProviderService();

        if ($otpProvider->usesServerCode()) {
            if (!$request->phone || !$request->password || !$request->code) return Common::apiResponse (0,'missing params',null,422);
        } else {
            if (!$request->phone || !$request->password|| !$request->firebase_id_token) return Common::apiResponse (0,'missing params',null,422);
        }
        $user = $request->user ();


        if ($user->phone != $request->phone) return Common::apiResponse (0,'phone number not register with your account',null,404);

        $rules = [
            'type' => ['required', 'boolean'],
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
        }

        if ($request->type){
            $user->tokens()->delete();
        }

        if ($otpProvider->usesServerCode()) {
            if (!$otpProvider->verify($request->phone, (string) $request->code)) {
                return Common::apiResponse(false, __('api_responses.invalid_code'));
            }
        } else {
            try {
                FirebaseValidate::validateIdToken($request->firebase_id_token);
            } catch (\Throwable $e) {
                return Common::apiResponse(false, __('api_responses.invalid_code'));
            }
        }

        $user->password = $request->password;
        $user->save();

        if ($otpProvider->usesServerCode()) {
            $otpProvider->consume($request->phone);
        }

        return Common::apiResponse (1,'reset successful',new UserResource($user));
    }

    public function resetWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook){
        $phone = $request->phone;
        if (!$phone || !$request->password) return Common::apiResponse (0, 'missing params', null, 422);
        $user = $request->user ();


        if ($user->phone != $phone) return Common::apiResponse (0, 'phone number not register with your account', null, 404);

        $rules = [
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
        }

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate){
            return Common::apiResponse(false, __('current phone not verified'));
        }
        $user = User::query ()->where ('phone', $phone)->first ();

        $user->password = $request->password;
        $user->save();
        return Common::apiResponse (1,'reset successful',new UserResource($user));
    }
}
