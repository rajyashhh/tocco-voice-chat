<?php

namespace Modules\Moment\Http\Controllers;

use App\Helpers\Common;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Moment\Entities\Moment;
use Modules\Moment\Entities\MomentLikes;
use Modules\Moment\Entities\ReportMoment;
use Modules\Moment\Transformers\MomentResource;
use DB;
use Illuminate\Support\Facades\Log;
use Modules\Moment\Http\Services\MomentService;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;

class MomentController extends Controller
{
    public $permission_name = 'report-moment';
    public function __construct(public MomentService $momentService) {}

    public function index(Request $request)
    {
        $type = $request->type;
        $page = $request->get('page', 1);
        $userId = $request->user_id;
        $currentUser = Auth::id();

        if (!$page || $page == 1) {
            $user = Auth::user();
            //            $user->moment_type = $user->id . random_int(1000, 9999);
            //            $user->save();
        }

        $data = $this->momentService->getMomentsByType($type, $userId, $page, $currentUser);

        if (!$data) {
            return Common::apiResponse(1, 'Please select a valid type', '', 200);
        }

        return Common::apiResponse(1, '', MomentResource::collection($data), 200);
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $contacts = $request->contacts ?? '';
        // Delegate to the service layer
        $result = $this->momentService->createMoment($contacts, $request);

        // Format and return response
        return Common::apiResponse($result['success'], $result['message']);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */


    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */


    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show(Request $request, $id)
    {
        $userId = Auth::id();

        // Delegate to the service
        $result = $this->momentService->getMoment($id, $userId);

        // Format and return response
        if (!$result['success']) {
            return Common::apiResponse(0, $result['message'], $result['status']);
        }

        return Common::apiResponse(1, $result['message'], new MomentResource($result['data']), $result['status']);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    // public function update(Request $request, $id)
    // {
    //     //
    // }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $result = $this->momentService->deleteMomentById($id);

        // Return the response
        return Common::apiResponse($result['success'] ? 1 : 0, $result['message'], $result['status']);
    }

    public function recordView($id)
    {
        Moment::where('id', $id)->increment('views_count');
        return Common::apiResponse(1, 'success');
    }

    public function destroy_dash($moment_id, $id)
    {
        if (!Admin::user()->can('*')){
            Permission::check('delete-'.$this->permission_name);
        }
        $result = $this->momentService->deleteMomentAndReport($moment_id, $id);

        // Check result and return the appropriate response
        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back();
    }
}
