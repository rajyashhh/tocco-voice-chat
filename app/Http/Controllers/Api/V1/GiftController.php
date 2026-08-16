<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Models\Gift;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Tik\Services\GiftService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\GiftResource;
use Illuminate\Support\Facades\Validator;

class GiftController extends Controller
{
    public function __construct(private GiftService $giftService) {}
    public function index(Request $request)
    {
        $type = $request->type;
        $gifts = $this->giftService->index($type);
        return Common::apiResponse(true, '', GiftResource::collection($gifts), 200);
    }
    public function getByCategory(Request $request)
    {

        // NOTE: legacy clients send the category id in the `type` param, so the same
        // value is fed into both $categoryId and $type below. We intentionally keep
        // this behavior; invalidation no longer depends on the cache key shape since
        // the whole 'gifts' tag is flushed on any gift/category mutation.
        $categoryId = $request->input('type');
        $type       = $request->input('type');
        $gifts = $this->giftService->getByCategory($categoryId, $type);
        return Common::apiResponse(true, '', GiftResource::collection($gifts), 200);
    }
    
    public function get_images(Request $request)
    {

        $gifts = $this->giftService->get_images();
        return Common::apiResponse(true, '', $gifts, 200);
    }


    public function allGifts(Request $request)
    {
        $gifts = $this->giftService->allGift($request->page, $request->per_page);
        return Common::apiResponse(1, '',  $gifts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'nullable|string|max:255',
            'e_name'         => 'nullable|string|max:255',
            'type'         => 'required|numeric',
            'vip_level'         => 'nullable|lt:256',
            'price'         => 'required|numeric',
            'img'          => 'required|mimes:jpeg,png,jpg,gif',
            'show_img'          => 'required|mimes:jpeg,png,jpg,gif,svg,mp4,svga,ZZ',
            'image_type'         => 'required|string|max:255',
            'show_img2'          => 'nullable|mimes:jpeg,png,jpg,gif,svg',
            'sort'         => 'nullable|numeric',
            'enable'         => 'nullable|boolean',
            'music_gift'         => 'nullable|boolean',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $this->giftService->create($request);

        return Common::apiResponse(1, 'created successfully');
    }

    public function storeList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'nullable|string|max:255',
            'e_name'         => 'nullable|string|max:255',
            'type'         => 'required|numeric',
            'vip_level'         => 'nullable|lt:256',
            'price'         => 'required|numeric',
            'img'          => 'required',
            'show_img'          => 'required',
            'image_type'         => 'required|string|max:255',
            'show_img2'          => 'nullable|mimes:jpeg,png,jpg,gif,svg',
            'sort'         => 'nullable|numeric',
            'enable'         => 'nullable|boolean',
            'music_gift'         => 'nullable|boolean',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try{
        $this->giftService->create($request);

        return Common::apiResponse(1, 'created successfully');
        }catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }






    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gift_id'  => 'required|integer|exists:gifts,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->giftService->show($request->gift_id);
        return Common::apiResponse(1, '', $data);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gift_id'  => 'required|integer|exists:gifts,id',
            'name'         => 'nullable|string|max:255',
            'e_name'         => 'nullable|string|max:255',
            'type'         => 'required',
            'vip_level'         => 'nullable|lt:256',
            'price'         => 'required|numeric',
            'img'          => 'nullable|mimes:jpeg,png,jpg,gif',
            'show_img'          => 'nullable|mimes:jpeg,png,jpg,gif,svg,mp4,svga,ZZ',
            'image_type'         => 'nullable|string|max:255',
            'show_img2'          => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sort'         => 'nullable|numeric',
            'enable'         => 'nullable|boolean',
            'music_gift'         => 'nullable|boolean',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->giftService->update($request);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(1, 'updated successfully');
    }

    public function musicSwitchUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'music_gift' => 'required|boolean',
            'gift_id' => 'required|integer|exists:gifts,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $value =   $this->giftService->updateSwitch($request->music_gift, $request->gift_id, 'music_gift');
        if (!$value)  return Common::apiResponse(1, 'failed');
        return Common::apiResponse(1, 'updated successfully');
    }

    public function enableSwitchUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enable' => 'required|boolean',
            'gift_id' => 'required|integer|exists:gifts,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $value =   $this->giftService->updateSwitch($request->enable, $request->gift_id, 'enable');
        if (!$value)  return Common::apiResponse(1, 'failed');
        return Common::apiResponse(1, 'updated successfully');
    }

    public function isPlaySwitchUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_play' => 'required|boolean',
            'gift_id' => 'required|integer|exists:gifts,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $value =   $this->giftService->updateSwitch($request->is_play, $request->gift_id, 'is_play');
        if (!$value)  return Common::apiResponse(1, 'failed');
        return Common::apiResponse(1, 'updated successfully');
    }

    public function typeGift(Request $request)
    {
        return translate(TYPE_GIFT);
    }
}
