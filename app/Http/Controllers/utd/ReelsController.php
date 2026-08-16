<?php

namespace App\Http\Controllers\utd;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\RealResource;
use App\Tik\Services\ReelsService;
use App\Models\Config;
use Illuminate\Http\Request;
use Exception;

class ReelsController extends Controller
{
    public function __construct(private ReelsService $reelService) {}

    public function index(Request $request)
    {
        try {
            $reels = $this->reelService->index($request->per_page ?? 10, $request->Page ?? 1);
            return Common::apiResponse(true, 'success', RealResource::collection($reels));
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function following(Request $request)
    {
        try {
            $reels = $this->reelService->following($request->per_page ?? 10, $request->Page ?? 1);
            return Common::apiResponse(true, 'success', RealResource::collection($reels));
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function user($id, Request $request)
    {
        try {
            $reels = $this->reelService->showByUser($id);
            return Common::apiResponse(true, 'success', RealResource::collection($reels));
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $reel = $this->reelService->show($id);
            return Common::apiResponse(true, 'success', new RealResource($reel));
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function view($id)
    {
        try {
            $this->reelService->recordView($id);
            return Common::apiResponse(true, 'success', null);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function search($id, Request $request)
    {
        try {
            $reel = $this->reelService->search($id);
            return Common::apiResponse(true, 'success', new RealResource($reel));
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $this->reelService->delete($id);
            return Common::apiResponse(true, 'success', null);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), null, 400);
        }
    }

    public function reelConfig($num, Request $request)
    {
        $conf = Config::where('name', 'upload_reel')->first();
        if (!$conf) {
            Config::create(['name' => 'upload_reel', 'value' => $num]);
        } else {
            $conf->update(['value' => $num]);
        }
        return Common::apiResponse(true, __('dashboard.update'), null);
    }
}
