<?php

namespace Modules\SwitchAccount\Http\Controllers;

use App\helper\AccountHelper;
use App\helper\TryCatchHelper;
use App\Models\User;
use Dotenv\Util\Str;
use App\Helpers\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\ChatMessage;
use Illuminate\Contracts\Support\Renderable;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\SwitchAccount\Entities\UserAccount;
use Modules\SwitchAccount\Transformers\AccountResource;

class SwitchAccountController extends Controller
{


    public function add_account(Request $request)
    {
        $user = $request->user();

        if (!$request->token_new_account) {
            return Common::apiResponse(false, 'missing params', null, 422);
        }

        return TryCatchHelper::handle(function () use ($request, $user) {
            $otherUser = $this->getOtherUser($request);

            if ($otherUser->id == $user->id) {
                throw new \Exception('can not add yourself');
            }

            $userAccount = AccountHelper::linkAccountWithDevice(
                $user->id,
                $otherUser->id,
                $user->device_token
            );

            $accounts = $this->getAllAccounts($user->id, $otherUser->id, $user->device_token);

            return [
                'current' => [
                    'image'      => $user->profile->avatar,
                    'name'       => $user->name,
                    'key'        => $userAccount->key,
                    'expire'     => $userAccount->expire,
                    'can_switch' => false,
                ],
                'other' => AccountResource::collection($accounts),
            ];
        }, 200, 400);
    }
    public function add_account0(Request $request)
    {
        $user = $request->user();
        if (!$request->token_new_account) return Common::apiResponse(0, 'missing params', null, 422);
        try {
            $otherUser = $this->getOtherUser($request);
            if ($otherUser->id == $user->id) return Common::apiResponse(0, 'can not add yourself', null, 422);
            $key = \Illuminate\Support\Str::uuid();
            $found = UserAccount::where(function ($q) use ($otherUser) {
                $q->where('child_user_id', $otherUser->id)->orWhere('parent_user_id', $otherUser->id);
            })->first();

            if ($found) {
                return Common::apiResponse(0, 'account is related to another account', null, 422);
            }
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        UserAccount::firstOrCreate([
            'parent_user_id' => $user->id,
            'child_user_id' => $otherUser->id,
            'device_token' => $user->device_token,
        ], [
            'key' => $key,
            'expire' => 30,
        ]);
        $accounts = $this->getAllAccounts($user->id, $otherUser->id, $user->device_token);
        $user_acount = UserAccount::query()->where(function ($q) use ($user) {
            $q->where("parent_user_id", $user->id)->orWhere("child_user_id", $user->id);
        })->first();
        $data = [
            'current'       => [
                'image'         =>  $user->profile->avatar,
                'name'          =>  $user->name,
                'key'           =>  $user_acount->key,
                'expire'        =>  $user_acount->expire,
                'can_switch'    =>  false
            ],
            'other'         => AccountResource::collection($accounts)
        ];
        return Common::apiResponse(1, 'success', $data, 200);
    }

    public function getAccounts($userId, $otherUserId, $deviceToken)
    {

        if (empty($deviceToken))  return  [];

        $users = UserAccount::
            //            where(function ($q) use ($userId,$otherUserId){
            //                $q->where("parent_user_id", $userId)
            //                    ->orWhere("child_user_id", $userId)
            //                    ->orWhere("child_user_id", $otherUserId)
            //                    ->orWhere("parent_user_id", $otherUserId);
            //            })
            where('device_token', $deviceToken)
            ->get();

        $parentUserIds = $users->pluck('parent_user_id');
        $childUserIds = $users->pluck('child_user_id');

        $allIds = $parentUserIds->merge($childUserIds)->unique()->values()->all();

        $filteredIds = array_filter($allIds, function ($id) use ($userId) {
            return $id != $userId;
        });
        $filteredIds = array_values($filteredIds);
        $accounts = User::query()->whereIn('id', $filteredIds)->get();

        return $accounts ?? [];
    }

    public function getAllAccounts($userId, $otherUserId, $deviceToken)
    {
        if (empty($deviceToken)) return [];

        $accounts = UserAccount::query()
            ->where('device_token', $deviceToken)
            ->where(function ($query) use ($userId) {
                $query->where('parent_user_id', $userId)
                    ->orWhere('child_user_id', $userId);
            })
            ->get();
    

        $userIds = $accounts->flatMap(function ($account) {
            return [$account->parent_user_id, $account->child_user_id];
        })->unique()->filter(function ($id) use ($userId) {
            return $id != $userId;
        })->values();

        return User::whereIn('id', $userIds)->get();

    }

    public function getOtherUser($request)
    {
        $bearerToken = $request->token_new_account;

        if (strpos($bearerToken, '|') !== false) {
            [$id, $bearerToken] = explode('|', $bearerToken, 2);
        }
        $token = hash('sha256', $bearerToken);

        $tokenAccount = DB::table('personal_access_tokens')->where('tokenable_type', "App\Models\User")->where('token', $token)->first();
        // dd($token,$tokenAccount);
        if (!$tokenAccount) throw new \Exception('user token not found');
        $otherUser = User::find($tokenAccount->tokenable_id);
        return $otherUser;
    }

    public function switch_account(Request $request)
    {
        $user = $request->user();
        if (!$request->key) return Common::apiResponse(0, 'missing params', null, 422);
        if (!$request->token) return Common::apiResponse(0, 'token not valid', null, 422);
        $user_account = UserAccount::query()->where("key", $request->key)->first();
        if (!$user_account) return Common::apiResponse(0, 'missing params', null, 422);

        $currentUserId = $user->id;
        //        info('current user'.$currentUserId);
        //        info('parent id'.$user_account->parent_user_id);
        //        info('child id'.$user_account->child_user_id);
        if (
            $user_account->parent_user_id != $currentUserId &&
            $user_account->child_user_id != $currentUserId
        ) {
            return Common::apiResponse(0, __('forbidden'), null, 403);
        }

        $otherUser = $user_account->parent_user_id === $currentUserId
            ? $user_account->childUser
            : $user_account->parentUser;

        $validToken = $this->isTokenFromLastTwoWeeks($otherUser, $request->token);
        if (!$validToken) return Common::apiResponse(0, 'token not valid', null, 422);

        $new_account = User::find($request->id);
        $token = $new_account->createToken('api_token')->plainTextToken;
        $user->is_logout = 1;
        $user->save();
        $new_account->is_logout = 0;
        $new_account->save();
        $new_account->auth_token = $token;
        
        $data = [
            'id'            => $new_account->id,
            'is_first'      => @(bool)$new_account->is_points_first,
            'auth_token'    => $new_account->auth_token
        ];
        AccountHelper::linkLoginAccountWithDevice($new_account->id, $new_account->device_token);

        return Common::apiResponse(1, 'success', $data, 200);
    }

    public function isTokenFromLastTwoWeeks($otherUser, $tokenString): bool
    {
        
        [$id, $plainToken] = explode('|', $tokenString);

        $token = $otherUser->tokens()->find($id);
        //        info('id'.$token);
        //        if (! $token){
        //            info('no token');
        //            return false;
        //        }
        //        if (! hash_equals($token->token, hash('sha256', $plainToken))){
        //            info('no hash equals');
        //            return false;
        //        }
        //        if (! $token->created_at >= Carbon::now()->subDays(14) ){
        //            info('its less than 14 days');
        //            return false;
        //        }
        //        $token = PersonalAccessToken::find($id);
        if (
            $token &&
            hash_equals($token->token, hash('sha256', $plainToken)) &&
            $token->created_at >= Carbon::now()->subDays(14)
        ) {
            return true;
        }

        return false;
    }

    public function myAccounts()
    {
        $user = \Auth::user();
        $currentUser = User::find($user->id);
        $chats_id = ChatRoom::where('user_id', $user->id)->orWhere('user_id2', $user->id)->pluck('id')->toArray();
        $total_unread_message =  ChatMessage::whereIn('chat_room_id', $chats_id)->where('user_id', 'not Like', $user->id)->where('status', 'not Like', 'seen')->count();

        $accounts = $this->getAllAccounts($user->id, 0, $user->device_token);
        $user_acount = UserAccount::query()->where(function ($q) use ($user) {
            $q->where("parent_user_id", $user->id)->orWhere("child_user_id", $user->id);
        })->first();

        $frame = $this->getFrame($currentUser);
        $data = [
            'current'           => [
                'image'         =>  $currentUser->profile->avatar,
                'name'          =>  $currentUser->name,
                'uuid'          => $currentUser->uuid,
                'user_type'     => $currentUser->type_user,
                'sender_level'  => $currentUser->sender_level ?? 0,
                'received_level'  => $currentUser->received_level ?? 0,
                'unread_messages'  => $total_unread_message ?? 0,
                'key'           =>  $user_acount?->key,
                'expire'        =>  $user_acount?->expire,
                'can_switch'    =>  false,
                'vip' => Common::ovip_center($currentUser),
                'special_color'    => @$currentUser->color_id ?? '',
                'special_id'          =>  @$currentUser->specialId?->ware?->id ?? 0,
                'special_id_image'          =>  @$currentUser->specialId?->ware?->show_img ?? "",
                'image_color'          => @$currentUser->color_image ?? (object)[],
                'level' => [
                    'receiver_img' => $currentUser->getImageReceiverOrSender('receiver_id', 1)->img ?? '',
                    'sender_img' => $currentUser->getImageReceiverOrSender('sender_id', 2)->img ?? '',
                ],
                'user_types' => $currentUser->user_types,
                'frame' => $frame,
                'frame_id' => $frame ? @$currentUser->dress_1 : 0,
                'country' => @$currentUser->country ? [
                    'id' => @$currentUser->country->id,
                    'name' => @$currentUser->country->name ?? '',
                    'flag' => @$currentUser->country->flag ?? '',
                    'language' => @$currentUser->country->language ?? '',
                    'e_name' => @$currentUser->country->e_name ?? '',
                    'phone_code' => @$currentUser->country->phone_code ?? '',
                    'iso' => substr(@$currentUser->country->iso, 0, 2),
                ] : null,
            ],
            'other'         => AccountResource::collection($accounts)
        ];
        return Common::apiResponse(1, 'success', $data, 200);
    }

    private function getFrame(User $user)
    {
        $dress_1_data = $this->getUserDress($user, 4, $user->dress_1, 'img2');
        $dress_1_fallback = $this->getUserDress($user, 4, $user->dress_1, 'img1');
        $frame = $dress_1_data ?: $dress_1_fallback;
        return $frame;
    }

    private function getUserDress(User $user, $type, $dress, $item = 'img1')
    {
        $pack = $user->packs?->where('is_used', 1)
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();
        return $pack && $pack->ware ? $pack->ware->{$item} : '';
    }
}
