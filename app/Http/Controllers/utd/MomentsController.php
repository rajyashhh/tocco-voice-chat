<?php

namespace App\Http\Controllers\utd;

use App\Models\Config;
use Exception;
use App\Helpers\Common;
use App\Models\ImageColor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\MomentResource;
use App\Tik\Services\MomentsService;
use Illuminate\Support\Facades\Validator;
use Modules\Moment\Entities\MomentCommint;
use Modules\Moment\Entities\MomentLikes;
use Modules\Moment\Transformers\MomentDashboardResource;

class MomentsController extends Controller
{

    public function __construct(private MomentsService $MomentsService) {}

    public function all(Request $request)
    {
        $data = $this->MomentsService->all($request->id, $request->per_page, $request->page);

        return Common::apiResponse(true, 'done', MomentResource::collection($data) );
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'description' => 'required|string',
            'user_id' => 'required',
            'img' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }


        try {
            $this->MomentsService->create($request);
            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [

            'name' => 'required|string',
            'img' => 'required|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $this->MomentsService->update($id, $request);
            return Common::apiResponse(true, 'updated successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function show($id)
    {
        $data = $this->MomentsService->show($id);

        return Common::apiResponse(true, 'done', new MomentResource($data) );
    }

    public function destroy($id)
    {
        try {
            $this->MomentsService->delete($id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function search($uuid, Request $request)
    {

        try {
            $moment = $this->MomentsService->search($uuid);
            return Common::apiResponse(true, 'success', MomentResource::collection($moment) );
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function get_user_moments($user_id, Request $request)
    {

        try {
            $reels = $this->MomentsService->get_user_moments($user_id);
            // return $reels;
            return Common::apiResponse(true, 'success', MomentDashboardResource::collection($reels));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function config(Request $request)
    {
        $conf = Config::where('name', 'upload_moment')->first();
        if (!$conf) {
            config::create([
                'name'  => 'upload_moment',
                'value' => $request->num,
            ]);
        } else {
            $conf->value = $request->num;
            $conf->save();
        }
        return Common::apiResponse(true, __('dashboard.update'), null);
    }
}
