<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Moment\Entities\ReportMoment;
use Modules\Moment\Http\Services\MomentService;
use App\Tik\Services\RequestBackgroundImagService;

class ReportMomentController extends Controller
{
    public function __construct(public MomentService $momentService) {}


    public function all(Request $request)
    {
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        $data = ReportMoment::when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->with('moment')->paginate($perPage, ['*'], 'page', $page);
        return Common::apiResponse(true, 'done', $data);
    }

    public function show($id)
    {
        try {
            $data = ReportMoment::findOrFail($id);
            return Common::apiResponse(1, 'done',  $data, 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'moment_id' => 'required|integer',
            'Reporter_id' => 'required|integer',
            'Reported_id' => 'required|integer',
            'description' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }


        try {
            ReportMoment::create($request->all());
            return Common::apiResponse(1, 'done', 'created successfully', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [

            'moment_id' => 'required|integer',
            'Reporter_id' => 'required|integer',
            'Reported_id' => 'required|integer',
            'description' => 'required',
            'type' => 'required',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            ReportMoment::where('id', $id)->update($request->all());
            return Common::apiResponse(1, 'done', 'updated successfully', 200);
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage(), 422);
        }
    }

    public function destroy($id)
    {
        try {
            ReportMoment::where('id', $id)->delete();
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroyDash($moment_id, $id)
    {
     return  $this->momentService->deleteMomentAndReport($moment_id, $id);

       
    }
}
