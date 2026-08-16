<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Models\Agency;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Tik\Services\AgencyService;
use App\Http\Controllers\Controller;
use App\Http\Resources\RequestJoinAgency;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ActiveAgencyResource;
use App\Http\Resources\AgencyRequestsResource;
use App\Http\Resources\ActiveAgencyMembersResource;


class AgencyController extends Controller
{
    public function __construct(private AgencyService $agencyService) {}

    public function index(Request $request)
    {
        try {
            $data = $this->agencyService->allRequests($request->id, $request->uuid, $request->per_page, $request->page, $request->status, $request->action);
            return Common::apiResponse(true, 'success', AgencyRequestsResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function actionRequestAgency(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'agency_id' => 'required|integer|exists:agencies,id',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->agencyService->actionRequestAgency($request);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
        if ($request->status != 1) {
            return Common::apiResponse(1, 'deleted request',  200);
        } else {
            return Common::apiResponse(1, 'accept request',  200);
        }
    }

    public function activeAgencies(Request $request)
    {
        try {
            $data = $this->agencyService->activeAgencies($request->id,  $request->per_page,  $request->page);
            return Common::apiResponse(true, 'success', ActiveAgencyResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allAgencies(Request $request)
    {
        try {
            $data = $this->agencyService->allActiveAgencies($request->id);
            $data = $data->map(function ($agency) {
                return [
                    'id' => $agency->id,
                    'name' => $agency->name,
                ];
            });
            return Common::apiResponse(true, 'success', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function activeAgenciesMembers(Request $request)
    {
        try {
            $data = $this->agencyService->agencyById($request->id);

            $members = $data->mempers()
                ->withSum(['monthlyDiamondReceive as monthly_diamond_received' => function ($q) {
                    $q->where('month', now()->month)
                        ->where('year', now()->year);
                }], 'monthly_diamond_received')
                ->orderBy('monthly_diamond_received', 'desc')
                ->with([
                    'userSallary' => function ($query) {
                        $query->select('id', 'user_id', 'sallary')
                            ->where('month', now()->month)
                            ->where('year', now()->year);
                    },
                    'profile:id,user_id,avatar'
                ])
                ->get(['id', 'uuid', 'total_days', 'name', 'monthly_diamond_received']);


            return Common::apiResponse(true, 'success', $members);
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function delete(Request $request)
    {
        try {
            $data = $this->agencyService->deleteAgency($request->id);
            return Common::apiResponse(true, 'deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function changeAgencyMembers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_agency_id' => 'required|integer|exists:agencies,id',
            'new_agency_id' => 'required|integer|exists:agencies,id',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $data = $this->agencyService->changeAgencyMembers($request->old_agency_id, $request->new_agency_id);
            return Common::apiResponse(true, 'changes successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allAgenciesExceptOld(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_agency_id' => 'required|integer|exists:agencies,id',
        ]);

        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }

        try {
            $data = $this->agencyService->AllAgencyExceptOld($request->old_agency_id, $request->search, $request->per_page, $request->page);
            return Common::apiResponse(true, 'success', AgencyRequestsResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_owner_id' => 'required|integer|exists:users,id',
            'name' => 'required|string',
            'phone' => 'required|string',
            'notice' => 'required',
            'url' => 'nullable',
            'img' => 'required|mimes:jpeg,png,jpg',
            'contents' => 'nullable',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->agencyService->createAgencyUtd($request);
            return Common::apiResponse(1, 'created successfully',  200);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_owner_id' => 'required|integer|exists:users,id',
            'name' => 'required|string',
            'phone' => 'required|string',
            'notice' => 'required',
            'url' => 'nullable',
            'img' => 'required|mimes:jpeg,png,jpg',
            'contents' => 'nullable',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->agencyService->updateAgencyUtd($id, $request);
            return Common::apiResponse(1, 'updated successfully',  200);
        } catch (Exception $e) {
            return Common::apiResponse(false, $e->getMessage(), null, 407);
        }
    }

    public function show($id)
    {
        try {
            $data = $this->agencyService->show($id);
            return Common::apiResponse(true, 'success', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function destroy($id)
    {
        try {
            $this->agencyService->destroy($id);
            return Common::apiResponse(true, ' deleted successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function allAgencyJoinRequest(Request $request)
    {
        $data = $this->agencyService->getAllRequestUtd($request->status, $request->agency_id, $request->id, $request->per_page, $request->page);
        return Common::apiResponse(true, 'success', RequestJoinAgency::collection($data));
    }

    public function showAgencyJoinRequest($id)
    {
        try {
            $data = $this->agencyService->showAgencyJoinRequest($id);
            return Common::apiResponse(true, 'success', $data);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateAgencyJoinRequest($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'agency_id' => 'required|integer|exists:agencies,id',
            'status' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        try {
            $this->agencyService->updateAgencyJoinRequest($id, $request);
            return Common::apiResponse(true, ' update successfully');
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public static function by_agency_filter()
    {
        $ops = [0 => 'no agency'];
        $agencies = Agency::query()->where('status', 1)->get();
        foreach ($agencies as $agency) {
            $ops[$agency->id] = $agency->name;
        }
        return $ops;
    }
}
