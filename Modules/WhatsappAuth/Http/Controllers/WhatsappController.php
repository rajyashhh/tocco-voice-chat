<?php

namespace Modules\WhatsappAuth\Http\Controllers;

use App\Facades\RedisService;
use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Moment\Transformers\UserResource;
use Modules\WhatsappAuth\Entities\WhatsappMessage;
use Modules\WhatsappAuth\Entities\WhatsappWebhookValidate;
use Modules\WhatsappAuth\Services\ClientService;
use  Modules\WhatsappAuth\Services\WhatsappWebhook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WhatsappController extends Controller
{

    public function index()
    {
        $expireAt = request()->expires_at ?? 10;
        $whatsappMessage  = WhatsappMessage::first();
        $message = app()->getLocale() == 'ar' ? $whatsappMessage?->text_ar : $whatsappMessage?->text_en;

        $data          = ['expire_at' => $expireAt, 'message' => $message];
        $redirectLinks = $this->getLinksFromServer($data);

        return response()->json($redirectLinks);
    }

    public function registerWithWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(false, implode(',', $validator->errors()->messages()));
        }

        $phone = $request->phone;

        if (User::query()->where('phone', $phone)->exists()) {
            return Common::apiResponse(0, __('whatsappauth::whatsapp.exists'), null, 405);
        }

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate) {
            return Common::apiResponse(false, __('whatsappauth::whatsapp.whatsappValidation'));
        }

        $user = User::query()->create([
            'password' => $request->password,
            'status' => 1,
            ...$whatsappWebhookValidate->getUserData()
        ]);
        //Add vip when login
        UserHandling::AddUserVip($user, 'register');

        $token = $user->createToken('api_token')->plainTextToken;
        // UserHandling::AddUserVip($user,'register');
        $user->auth_token = $token;
        $user->is_points_first = 1;



        return Common::apiResponse(
            true,
            __('api_responses.logged'),
            [
                'id'            => $user->id,
                'name' => $user->name ?? '',
                'is_first'      => @(bool)$user->is_points_first,
                'auth_token'    => $user->auth_token
            ]
        );
    }

    public function resetWhatsappN(Request $request, WhatsappWebhook $whatsappWebhook)
    {
        if (!$request->phone || !$request->password) return Common::apiResponse(0, 'missing params');
        $phone = $request->phone;

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate) {
            return Common::apiResponse(false, __('current phone not verified'));
        }

        $user = User::query()->where('phone', $request->phone)->first();
        $user->password = $request->password;
        $user->save();
        return Common::apiResponse(1, 'reset successful', null);
    }

    public function whatsappWebhook(Request $request)
    {
        $data = $request->all();

        if ($request->status == 'validated') {
            $request['uuid'] = @$request->uuid ?? rand(1, 555555);
            $request['requested_at'] = Carbon::parse($request->requested_at)->toDateTimeString();
            $request['expires_at'] = Carbon::parse($request->expires_at)->toDateTimeString();
            WhatsappWebhookValidate::create($request->all());
            //create user
        }
        return response()->json();
    }

    public function changePhoneWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook)
    {
        $phone = $request->phone;
        if (!$phone) return Common::apiResponse(0, 'missing params', null, 422);
        $user = $request->user();
        $rules = [
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
        ];
        if ($user->phone == $phone) return Common::apiResponse(0, 'Old phone is wronge', null, 404);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
        }

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate) {
            return Common::apiResponse(false, __('current phone not verified'));
        }

        $user->phone = $phone;

        $user->save();
        return Common::apiResponse(1, 'reset successful', new UserResource($user));
    }

    // public function resetWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook){
    //     $phone = $request->phone;
    //     if (!$phone || !$request->password) return Common::apiResponse (0, 'missing params', null, 422);
    //     $user = $request->user();

    //     if ($user->phone != $phone) return Common::apiResponse (0, 'phone number not register with your account', null, 404);

    //     $rules = [
    //         'phone' => [
    //             'required',
    //             Rule::unique('users', 'phone')->ignore($user->id),
    //         ],
    //     ];
    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         return Common::apiResponse(0, 'Validation failed', $validator->errors(), 422);
    //     }

    //     $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
    //     if (!$whatsappWebhookValidate){
    //         return Common::apiResponse(false, __('current phone not verified'));
    //     }
    //     $user = User::query ()->where ('phone', $phone)->first ();

    //     $user->password = $request->password;
    //     $user->save();
    //     return Common::apiResponse (1,'reset successful',new UserResource($user));
    // }
    public function resetWhatsapp(Request $request, WhatsappWebhook $whatsappWebhook)
    {
        if (!$request->phone || !$request->password) return Common::apiResponse(0, 'missing params');
        $phone = $request->phone;

        $whatsappWebhookValidate = $whatsappWebhook->getLastValidatedPhone($phone);
        if (!$whatsappWebhookValidate) {
            return Common::apiResponse(false, __('current phone not verified'));
        }

        $user = User::query()->where('phone', $request->phone)->first();
        $user->password = $request->password;
        $user->save();
        return Common::apiResponse(1, 'reset successful', null);
    }

    private function getLinksFromServer(array $data)
    {
        $url = config('whatsappauth.server_url');
        $token = RedisService::get('whatsapp_token');

        $response = \Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get($url, $data);
        return $response->json();
    }

    public function sendCodeWhatsapp(Request $request)
    {
        $token = $request->header('Authorization');
        $safwaUrl = config('whatsappauth.base_url');
        $response =   Http::withHeaders([
            'Authorization' => 'Bearer '. $token,
            'Content-Type' => 'application/json',
        ])
            ->post($safwaUrl, [
                'code' => $request->code,
                'phone' => $request->phone,
            ]);

        $result = $response->json();

       return response()->json(['success' => $result], $result ? 200 : 402);
    }
}
