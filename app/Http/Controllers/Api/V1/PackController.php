<?php

namespace App\Http\Controllers\Api\V1;


use App\Http\Resources\Api\V1\MyPacksVipResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\Api\V2\MyPacksResource;
use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Tik\Services\PackService;
use App\Http\Controllers\Controller;


class PackController extends Controller
{

    public function __construct( private PackService $packService)
    {

    }

    public function hide(Request $request)
    {
        $user         = $request->user();

        $privilegeArr = [
            'has_color_name' => 18,
            'anonymous'      => 17,
            'country'        => 13,
            'last_active'    => 20,
            'visit'          => 19,
            'room'           => 16,
            'sound_effect'   => 21
        ];
        $type         = $request->type;
        $this->changePackMode($type, $privilegeArr, $user, true);
        return Common::apiResponse(1, 'ok', null, 200);
    }

    public function un_hide(Request $request)
    {
        $user = $request->user();
        $privilegeArr = [
            'has_color_name' => 18,
            'anonymous'      => 17,
            'country'        => 13,
            'last_active'    => 20,
            'visit'          => 19,
            'room'           => 16,
            'sound_effect'   => 21
        ];
        $type         = $request->type;
        $this->changePackMode($type, $privilegeArr, $user, false);

        return Common::apiResponse(1, 'ok', null, 200);
    }

    public function changePackMode($type, $privilegeArr, User $user, $isAvailable)
    {

        try{
            $this->packService->changePackMode($type, $privilegeArr,$user,$isAvailable);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

    }


    public function my_pack(Request $request)
    {
        try {
            $data = $this->packService->userPack($request);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        if ($request->type == 22) return Common::apiResponse(1, '', MyPacksVipResource::collection($data));

        return Common::apiResponse(1, '', MyPacksResource::collection($data));
    }




    public function usePackItem(Request $request)
    {
        $user    = $request->user();
        $itemId = $request->item_id;
        if (!$itemId) return Common::apiResponse(0, 'missing params');

        try {
            $data = $this->packService->usedPack($user, $itemId);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'success', $data);
    }


    public function takeOff(Request $request)
    {
        $user = $request->user();
        $type = $request->type;
        $item = $request->item_id;
        if (!$type && !$item) return Common::apiResponse(0, 'missing params', null, 422);
        if (!in_array($type, [1, 2, 3, 4, 28])) return Common::apiResponse(0, 'type invalid', null, 403);

        $this->packService->updateDress($user, $type,$item);

        return Common::apiResponse(1, 'success', new UserResource($user));
    }

    public function takeOffV2(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['packs', 'profile', 'myroom', 'room.backgroundImage', 'room.background',  'family']);
        $type = $request->type;
        $item = $request->item_id;
        if (!$type && !$item) return Common::apiResponse(0, 'missing params', null, 422);
        if (!in_array($type, [1, 2, 3, 4])) return Common::apiResponse(0, 'type invalid', null, 403);

        $this->packService->updateDress($user, $type,$item);

        return Common::apiResponse(1, 'success', new \App\Http\Resources\Api\V2\UserResource($user));
    }

}
