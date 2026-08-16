<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Services\AllGameService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AllGameController extends Controller
{
    protected $allGameService;

    public function __construct(AllGameService $allGameService)
    {
        $this->allGameService = $allGameService;
    }

    public function index()
    {
        $data = $this->allGameService->getAllGamesData();
        return Common::apiResponse(1, '', $data);
    }

    public function inRoom()
    {
        $data = $this->allGameService->getInRoom();
        return Common::apiResponse(1, '', $data);
    }

    public function outRoom()
    {
        $data = $this->allGameService->getOutRoom();
        return Common::apiResponse(1, '', $data);
    }

    public function updateGame(Request $request)
    {
        $result = $this->allGameService->updateGame($request->game_id, $request->user());
        return Common::apiResponse($result['status'], $result['message'], $result['code']);
    }

    public function utdGameIndex()
    {
        $data = $this->allGameService->utdIndex();
        return Common::apiResponse(1, '', $data);
    }

    public function utdGameCreate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'url' => 'required|url',
            'mini_url' => 'required|url',
            'type' => 'required|integer',
            'is_enable' => 'nullable|boolean',
            'custom_id' => 'nullable',
            'hight_image' => 'nullable|string',
            'in_room'    => 'nullable|integer',
            'hight' => 'nullable|string',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $this->allGameService->createUtd($request);
        return Common::apiResponse(1, 'created successfully');
    }
    public function utdGameUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'url' => 'required|url',
            'mini_url' => 'required|url',
            'type' => 'required|integer',
            'is_enable' => 'nullable|boolean',
            'custom_id' => 'nullable',
            'hight_image' => 'nullable|string',
            'in_room'    => 'nullable|integer',
            'hight' => 'nullable|string',
            'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'game_id' => 'required|integer|exists:all_games,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $this->allGameService->updateUtd($request);
        return Common::apiResponse(1, 'updated successfully');
    }
    public function utdGameSwitchUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_enable' => 'required',
            'game_id' => 'required|integer|exists:all_games,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $value =   $this->allGameService->updateSwitch($request);
        if (!$value)  return Common::apiResponse(1, 'failed');
        return Common::apiResponse(1, 'updated successfully');
    }

    public function showGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_id' => 'required|integer|exists:all_games,id',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, implode(',', $validator->errors()->all()), null, 422);
        }
        $data = $this->allGameService->show($request->game_id);
        return Common::apiResponse(1, '', $data);
    }

    public function gameChargeDetails(Request $request)
    {
        $data = $this->allGameService->gameChargeDetails($request->date);
        return Common::apiResponse(1, '', $data);
    }
}
