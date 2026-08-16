<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\OVipNewResource;
use Exception;
use App\Helpers\Common;

use Modules\Vip\Services\Api\VipService;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Api\V1\VipResource;
use App\Http\Resources\Api\V1\OVipResource;
use Modules\Vip\Entities\Vip;
use Illuminate\Support\Facades\Cache;
use Modules\Public\Http\Services\UserCounterServices;
use Modules\Public\Http\Services\UpgradeLevelServices;




class VipController extends Controller
{
    public function __construct(private VipService $vipService) {}

    public function index()
    {
        $type = request()->type ?? 2;
        $vips = $this->vipService->vipIndex($type);
        return Common::apiResponse(true, 'success', VipResource::collection($vips));
    }

    public function vipList()
    {
        $data = $this->vipService->vipList();

        \request()->vipPrivileges = $data['all_privileges'];

        return Common::apiResponse(1, '', OVipResource::collection($data['o_vips']), 200);
    }
    public function vipUserList(Request $request)
    {
        $userId = $request->user()->id;
        $data = $this->vipService->vipUserList($userId);

        \request()->vipPrivileges = $data['all_privileges'];

        return Common::apiResponse(1, '', OVipNewResource::collection($data['o_vips']), 200);
    }

    public function buyVip(Request $request)
    {
        if (!$request->vip_id) return Common::apiResponse(0, 'missing param', null, 422);

        try {
            [$user, $countWares, $buyer, $exp] = $this->vipService->buyVip($request);

            (new UpgradeLevelServices())->buyAristocracy($buyer, $exp);

            (new UserCounterServices)->eventUser($user, 'mybag', $countWares);
            return Common::apiResponse(1, 'done', null, 201);
        } catch (\Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function vip_use(Request $request)
    {
        if (!$request->vip_id) {
            return Common::apiResponse(false, __("api_responses.missing_params"), null, 422);
        }
        try {
            $data = $this->vipService->userVip($request);
        } catch (\Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'success', $data, 200);
    }

    public function pack_use(Request $request)
    {
        if (!$request->pack_id) {
            return Common::apiResponse(false, __("api_responses.missing_params"), null, 422);
        }
        try {
            $data = $this->vipService->usePack($request);
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'success', $data, 200);
    }
    
    public function vip_send(Request $request)
    {
        if (!$request->user_id || !$request->vip_id) {
            return Common::apiResponse(false, __("api_responses.missing_params"), null, 422);
        }

        try {
            $data = $this->vipService->sendVip($request);
        } catch (\Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(1, 'success', $data, 200);
    }


    public function createWareVip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'name_en'         => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'image' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'img2' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'img2_type' => 'nullable|string',
            'vipPrivilege_id' => 'required|integer|exists:vip_privileges,id',
            'ovip_id' => 'required|integer|exists:o_vips,id',
            'ware_id' => 'nullable|integer|exists:wares,id',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->vipService->createWareVip($request);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function getWareVip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vipPrivilege_id' => 'required||integer|exists:vip_privileges,id',
            'ovip_id' => 'required|integer|exists:o_vips,id',


        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $data = $this->vipService->wareVip($request);
        } catch (\Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }

        return Common::apiResponse(1, 'success', $data, 200);
    }

    public function deleteWare(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ware_id' => 'required|integer|exists:wares,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->vipService->deleteWare($request->ware_id);
            return Common::apiResponse(1, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function badges(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|numeric|min:1|max:5',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        $badges =   $this->vipService->badges($request->type);
        return Common::apiResponse(true, 'success', $this->getLevelGroups());
    }

    private function getLevelGroups()
    {
        Cache::flush();
        // Use Cache::remember to cache the results for 1 hour
        return Cache::remember('levels_chunks', 3600, function () {
            // Fetch all rows for the given type and order by level
            $sender_vips = Vip::where('type', 2)->orderBy('level', 'asc')->get();
            $receiver_vips = Vip::where('type', 1)->orderBy('level', 'asc')->get();
            $charge_vip = Vip::where('type', 5)->orderBy('level', 'asc')->get();

            // Group rows into chunks of 9
            $sender_chunks = $sender_vips->chunk(10);
            $receiver_chunks = $receiver_vips->chunk(10);
            $charge_chunks = $charge_vip->chunk(10);

            // Transform each chunk into the desired structure
            $sender_levelGroups = $sender_chunks->map(function ($chunk) {
                return [
                    'minlevel' => $chunk->first()->level, // Minimum level in the chunk
                    'maxlevel' => $chunk->last()->level,  // Maximum level in the chunk
                    'badge' => $chunk->last()->img,
                ];
            });


            $receiver_levelGroups = $receiver_chunks->map(function ($chunk) {
                return [
                    'minlevel' => $chunk->first()->level, // Minimum level in the chunk
                    'maxlevel' => $chunk->last()->level,  // Maximum level in the chunk
                    'badge' => $chunk->last()->img,
                ];
            });

            $charge_levelGroups = $charge_chunks->map(function ($chunk) {
                return [
                    'minlevel' => $chunk->first()->level, // Minimum level in the chunk
                    'maxlevel' => $chunk->last()->level,  // Maximum level in the chunk
                    'badge' => $chunk->last()->img,
                ];
            });

            return ['sender' => $sender_levelGroups->toArray(), 'receiver' => $receiver_levelGroups->toArray(), 'charge' => $charge_levelGroups->toArray()]; // Convert collection to array
        });
    }
}
