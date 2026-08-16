<?php

namespace App\Http\Controllers\Api\V1;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Tik\Services\MusicService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MusicResource;
use Illuminate\Support\Facades\Validator;


class MusicController extends Controller
{
    public function __construct(private MusicService $musicService) {}

    public function index()
    {
        $data = $this->musicService->all();
        return Common::apiResponse(1, '', MusicResource::collection($data));
    }

    public function userMusic(Request $request)
    {
        $data = $this->musicService->userMusic($request->user()->id);
        return Common::apiResponse(1, '', MusicResource::collection($data));
    }

    public function destroyUserMusic($id): JsonResponse
    {
        $this->musicService->destroyUserMusic(auth()->id(), $id);
        return Common::apiResponse(1, 'deleted successfully');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url'         => 'required',
            'name'        => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->musicService->create($request->user()->id, $request->url, $request->image, $request->name);
            return Common::apiResponse(1, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
