<?php

namespace Modules\AgencyApp\Http\Controllers\Api\V2\Dashboard;

use Exception;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Tik\Services\AgencyService;
use Maatwebsite\Excel\Facades\Excel;
use Modules\AgencyApp\Exports\HostDailyDataExport;
use Modules\AgencyApp\Transformers\HostDailyReportResource;


class AgencyAppController extends Controller
{
    protected $agencyService;

    public function __construct(AgencyService $agencyService)
    {
        $this->agencyService = $agencyService;
    }

    public function agency_data(Request $request)
    {

        try {
            $data = $this->agencyService->dataAgency();
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        return Common::apiResponse(1, '', $data,  200);
    }

    public function host_report($id)
    {

        try {
            $data = $this->agencyService->hostReport($id);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }

        return Common::apiResponse(1, '', $data,  200);
    }

    public function host_daily_report(Request $request)
    {
        $date = $request->date;

        try {
            $hosts = $this->agencyService->hostDailyReport($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, '', HostDailyReportResource::collection($hosts),  200);
    }

    public function host_daily_export_data(Request $request)
    {
        $date = $request->date;

        try {
            $hosts = $this->agencyService->hostDailyReport($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        $data = HostDailyReportResource::collection($hosts);
        return Excel::download(new HostDailyDataExport($data), 'data.xlsx');
    }

    public function host_agency_edit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
            'notice' => 'nullable',
        ]);
        // $user = Auth::user();
        try {
            $agency = $this->agencyService->editAgency($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        return Common::apiResponse(1, '', $agency,  200);
    }
}
