<?php

namespace App\Http\Controllers\Api\V2\Auth;

use Exception;
use App\Models\User;
use App\Helpers\Common;
use App\Models\Country;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Helpers\FirebaseValidate;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Api\V1\MyDataResource;
use App\Http\Requests\Api\V2\Auth\RegisterRequest;
use Kreait\Firebase\Exception\Auth\FailedToVerifyToken;

class RegisterController extends Controller
{
    function countries()
    {
        $data = Country::select('id', 'name', 'e_name', 'flag')->get();
        return $data;
    }
    public function register(RegisterRequest $request)
    {
        $phone = $request->phone;

        if (!$phone || !$request->firebase_id_token) {
            return Common::apiResponse(false, __('api_responses.invalid_code'));
        }

        try {
            $firebaseUid = FirebaseValidate::validateIdToken($request->firebase_id_token);
        } catch (\Throwable $e) {
            return Common::apiResponse(false, __('api_responses.invalid_code'));
        }

        if (User::query()->where('phone', $phone)->exists()) {
            return Common::apiResponse(0, 'already exists', null, 405);
        }
        $user = User::query()->create(
            ['phone' => $phone, 'password' => $request->password, 'firebase_uuid' => $firebaseUid]

        );
        $user = User::find($user->id);

        if (\request('tags') && is_array(\request('tags'))) {
            $user->tags()->attach(\request('tags'));
        }
        $user->is_points_first = 1;
        $user->is_logout = 0;
        $user->save();
        if (!$request->country_id) {
            $country = Country::query()->where('phone_code', '101')->first();
            $user->country_id = @$country->id ?: 0;
            $user->save();
        }
        $token = $user->createToken('api_token')->plainTextToken;
        $user->auth_token = $token;
        return Common::apiResponse(
            true,
            __('api_responses.logged'),
            [
                'id'            => $user->id,
                'is_first'      => @(bool)$user->is_points_first,
                'auth_token'    => $user->auth_token
            ]
        );
    }
}
