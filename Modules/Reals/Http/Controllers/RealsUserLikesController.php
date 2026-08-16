<?php

namespace Modules\Reals\Http\Controllers;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Renderable;
use Modules\Reals\Http\Services\RealsService;
use Modules\Reals\Http\Requests\StoreRealComment;
use Modules\Reals\Http\Services\RealLikesService;
use Modules\Reals\Http\Services\RealCommentsService;
use Modules\Reals\Transformers\LikesResource;

class RealsUserLikesController extends Controller
{
    public $realLikesService;
    public $realsService;

    public function __construct(RealsService $realsService, RealLikesService $realLikesService) {
        $this->realsService        = $realsService;
        $this->realLikesService = $realLikesService;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($realId)
    {
        try {
            $real = $this->realsService->findOrFail($realId);
       } catch (\Throwable $th) {
          $real =null;
       }
        if ($real == null){
            return Common::apiResponse(0, 'real not founded', [], 402);
        }

        $paginateLikes = $this->realLikesService->showLikes($real);
        $data =  LikesResource::collection($paginateLikes);

        return Common::apiResponse(1, 'successful', $data, 200);

    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store($realId, Request $request)
    {

        $user = Auth::user();
        try {
            $real = $this->realsService->findOrFail($realId);
        } catch (\Exception $e) {
            $real = null;
        }
        if ($real == null){
            return Common::apiResponse(0, 'real not founded', [], 402);
        }
        $this->realLikesService->likeOrUnLike( $real, $user);
        return Common::apiResponse(1, 'success', [], 200);
    }


    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($realId, $id)
    {
        try {
            $real = $this->realsService->findOrFail($realId);
        } catch (\Exception $e) {
            $real = null;
        }
        if ($real == null){
            return Common::apiResponse(0, 'real not founded', [], 402);
        }
        $this->realLikesService->delete($id, $real);
    }


}
