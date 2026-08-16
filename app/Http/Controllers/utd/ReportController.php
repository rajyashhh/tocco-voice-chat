<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Tik\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function __construct(private ReportService $reportService) {}

    public function reports(Request $request)
    {
        try {
            $data = $this->reportService->report($request);
            return Common::apiResponse(true, 'done', $data);
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function eventReports(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $data = $this->reportService->eventReports($request);
            return Common::apiResponse(true, 'done', $data);
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function returnReward(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reward_id' => 'required|integer',
            'type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(
                0,
                __('api_responses.validation_error'),
                $validator->errors(),
            );
        }

        try {
            $data = $this->reportService->returnReward($request);

            return Common::apiResponse(true, 'done');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
