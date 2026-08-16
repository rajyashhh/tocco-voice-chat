<?php

namespace Modules\Reals\Http\Controllers;

use App\Models\User;
use App\Helpers\Common;
use Hamcrest\Arrays\IsArray;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Reals\Http\Requests\RealStore;
use Illuminate\Contracts\Support\Renderable;
use Modules\Reals\Http\Services\RealsService;
use Modules\Reals\Transformers\RealsResource;
use Modules\Public\Http\Services\UpgradeLevelServices;

class RealsController extends Controller
{

    public $realsService;

    public function __construct(RealsService $realsService)
    {
        $this->realsService = $realsService;
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $user = Auth::user();
        $filter = request('filter');
        if (!request("page")  || request("page") == 1) {
            $user->real_type = $user->id . random_int(1000, 9999);
        }
        $reals = $this->realsService->showNew($user, $filter);
        //return $reals;

        /*         return response()->json([
            'status' => true,
            'message' => 'success',
            'data' => RealsResource::collection($reals)->resolve(),
            'pagination' => [
                'current_page' => $reals->currentPage(),
                'per_page' => $reals->perPage(),
                'total' => $reals->total(),
                'last_page' => $reals->lastPage(),
            ]
            ],200); */

        return Common::apiResponse(1, 'success', RealsResource::collection($reals));
    }

    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserReals($user_id = null)
    {
    

        try {
            if ($user_id == null) {
                $user = Auth::user();
            } else {
                $user = User::query()->withoutAppends()->findOrFail($user_id);
            }
        } catch (\Exception $e) {
            return Common::apiResponse(false, 'user not found');
        }
        $reals = $this->realsService->getUserReals($user, Auth::id());
        $collection = RealsResource::collection($reals);
        /*$reals = json_decode($collection->toJson());
        $data = [
            'user' => ['id' => $user->id, 'name' => $user->name, 'image' => @$user->profile?->avatar ?? ''],
            'reals' => $reals
        ];*/
        return Common::apiResponse(true, 'success', $collection);
    }

    public function getMyReals()
    {
        $user_id=Auth::user()->id;
        try {
          
                $user = User::query()->withoutAppends()->findOrFail($user_id);
            
        } catch (\Exception $e) {
            return Common::apiResponse(false, 'user not found');
        }
        $reals = $this->realsService->getUserReals($user, Auth::id());
        $collection = RealsResource::collection($reals);
        
        return Common::apiResponse(true, 'success', $collection);
    }


    
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserFollowersReals(): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        if (!request("page")  || request("page") == 1) {
            $user->following_unique_value = $user->id . random_int(10000, 99999);
        }
        $reals = $this->realsService->getUserFollowersReals($user);

        $collection = RealsResource::collection($reals);

        return Common::apiResponse(true, 'success', $collection);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(RealStore $request)
    {
        $user = $request->user();
        $real = $this->realsService->create($request->all(), Auth::id());
        (new UpgradeLevelServices())->uploadReel($user);
        return Common::apiResponse(1, 'success', new RealsResource($real));
    }

    public function oldReal()
    {
        $this->realsService->oldReal();
    }

    public function show($real_id)
    {
        $real_id = intval($real_id);
        if (!$real_id) return Common::apiResponse(false, 'Missing parameters');
        $real = $this->realsService->showReal($real_id, Auth::id());
        if (!$real) return Common::apiResponse(false, 'No real founded');
        return Common::apiResponse(true, 'success', new  RealsResource($real));
    }


  
  

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $value = $this->realsService->delete($id);
        if (!$value) return Common::apiResponse(0, 'Not allow', null, 402);
        return Common::apiResponse(1, 'success');
    }

    public function recordView($id)
    {
        \Modules\Reals\Entities\Real::where('id', $id)->increment('views_count');
        return Common::apiResponse(1, 'success');
    }

    public function destroy_dash($real_id, $id)
    {
        $result = $this->realsService->deleteReeltAndReport($real_id, $id);

        // Check result and return the appropriate response
        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back();
    }

    public function update($real_id, Request $request)
    {

        try {
            $result = $this->realsService->update($real_id, $request->all());
    
            if (!$result) {
                return Common::apiResponse(0, 'Try later');
            }
    
            return Common::apiResponse(1, 'Success', $result);
        } catch (\Exception $e) {
            return Common::apiResponse(0, 'Error: ' . $e->getMessage());
        }

    }
}
