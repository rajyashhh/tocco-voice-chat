<?php

namespace Modules\SalaryTransaction\Http\Controllers\Api;

use App\Helpers\Common;
use App\Models\PaymentGateway;
use App\Models\ShippingAgency;
use Modules\SalaryTransaction\Helpers\TransactionCustomNotification;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\Config;
use App\Models\User;
use App\Models\UserSallary;
use Auth;
use Carbon\Carbon;
use Google\Service\CloudWorkstations\Host;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Modules\SalaryTransaction\Entities\AdminCheck;
use Modules\SalaryTransaction\Entities\AgencyTransferSalary;
use Modules\SalaryTransaction\Transformers\RequestsResource;
use Modules\SalaryTransaction\Entities\PendingSalaryRequest;
use Modules\SalaryTransaction\Entities\SalaryRequest;
use Modules\SalaryTransaction\Transformers\HostRequestsResource;

class SalaryTransactionController extends Controller
{
    public function add_request_salary(Request $request)
    {
        try {
            if(!$request->agent_id || !$request->payment_gateway_id || !$request->country_id || !$request->usd)
            {
                return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
            }
            if ($request->usd < 0 || (is_numeric($request->usd) && strpos($request->usd, '.') !== false)){
                return Common::apiResponse(0, __('api_responses.missing_params'), null, 422);
            }

            if (!PaymentGateway::find($request->payment_gateway_id)){
                return Common::apiResponse(0, __('salaryTransaction::api_responses.un_supported_payment_gateway'), null, 422);
            }
            $host = $request->user();
            if ($host->transfer_salary == 1) {
                return Common::apiResponse(0, __('api_responses.freeze_transfer_charger'), 404);
            }

            $agency = ShippingAgency::with("owner")->where("app_owner_id",$request->agent_id)->first();
            if (!$agency instanceof ShippingAgency) return Common::apiResponse(false, 'agency not found');
            // if ($agency->Shipping_agency != 1 ) {
            //     return Common::apiResponse(0, __('api_responses.agency_not_shipping'), null, 422);
            // }
            if ($agency->is_frozen == 1) {
                return Common::apiResponse(0, __('api_responses.frozen_agency'), 404);
            }
            $agency_owner = $agency->owner;
            $percentage_value = Common::getConfig('one_usd_value_in_coins')  ?? 10;
            $coin_usd = $request->usd * $percentage_value;

            $result = DB::transaction(function () use ($request, $host, $agency, $agency_owner, $coin_usd) {
                // Serialize concurrent requests for the same host under a row lock so the
                // duplicate-request check and the balance check are re-evaluated atomically:
                // blocks two simultaneous submissions from both passing and double-debiting.
                $lockedHost = User::where('id', $host->id)->lockForUpdate()->first();
                if (!$lockedHost) {
                    return ['code' => 0, 'msg' => 'agency not found', 'status' => 200, 'raw' => true];
                }

                if ($lockedHost->salary < $request->usd) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.dont_have_coin'), 'status' => 422];
                }

                $check_requests = SalaryRequest::where('host_id', $lockedHost->id)->whereIn("status", [0, 1, 2])->first();
                if ($check_requests != null) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.have_request_before'), 'status' => 422];
                }

                SalaryRequest::create([
                    "agency_id"             => $agency->id,
                    "agency_owner_id"       => $agency_owner->id,
                    "host_id"               => $lockedHost->id,
                    "status"                => 0,
                    "payment_gateway_id"    => $request->payment_gateway_id,
                    "country_id"            => $request->country_id,
                    "coins"                 => $coin_usd,
                    "usd"                   => $request->usd,
                    "note"                  => $request->note,
                ]);

                $this->updateUserSalary($lockedHost, $request->usd);

                return ['code' => 1, 'msg' => __('salaryTransaction::api_responses.request_added_success'), 'status' => 200, 'notify' => $agency_owner->id];
            });

            if (isset($result['notify'])) {
                TransactionCustomNotification::sendRequest($result['notify']);
            }

            if (isset($result['raw'])) {
                return Common::apiResponse(false, $result['msg']);
            }

            return Common::apiResponse($result['code'], $result['msg'], null, $result['status']);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function updateUserSalary(User $host,$amount)
    {

        PendingSalaryRequest::create([
            "user_id" => $host->id,
            "salary" => $amount,
            "status" => 0,
            "type" => 'salary_transaction',
        ]);

        $userSalary = UserSallary::where([ "user_id" => $host->id,"month" => date("m"),"year" => date("Y")]);
        if ($host->agency_id != null && $host->agency_id != 0) {
            $userSalary = $userSalary->where("user_agency_id",$host->agency_id);
        }else{
            $userSalary = $userSalary->orderByDesc("id");
        }
        $userSalary = $userSalary->first();

        if ($userSalary != null) {
            $userSalary->cut_amount += $amount;
            $userSalary->save();
        }else{
            UserSallary::create([
                "user_id" => $host->id,"month" => date("m"),"year" => date("Y") ,'cut_amount'=>$amount
            ]);
        }
    }

    public function get_requests()
    {
        $user = Auth::user();
        $type = request("type") ?? 0;
        $requests = SalaryRequest::whereHas("agency",function($q) use ($user){
            $q->where("app_owner_id",$user->id);
        })->where('status',$type)->paginate();

        $result = RequestsResource::collection($requests);
        return Common::apiResponse(1, '', $result);
    }

    public function action_request(Request $request)
    {
        if (!$request->request_id) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.missing_params'), null, 422);
        }
        if ($request->answer != 0 && $request->answer != 1) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.correct_data'), null, 422);
        }
        $user = $request->user();

        try {
            $result = DB::transaction(function () use ($request, $user) {
                $requestSalary = SalaryRequest::with(["host", "agency"])
                    ->lockForUpdate()
                    ->find($request->request_id);

                if (!$requestSalary) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.request_not_found'), 'status' => 422];
                }

                // Ownership must be verified before ANY financial side-effect.
                if ($requestSalary->agency?->app_owner_id != $user->id) {
                    return ['code' => 0, 'msg' => __('api_responses.not_agency_owner'), 'status' => 422];
                }

                // Re-check status under the row lock: blocks replay / concurrent double-refund.
                if ($requestSalary->status != 0) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.correct_data'), 'status' => 422];
                }

                $host = $requestSalary->host;

                if (Carbon::parse($requestSalary->created_at)->addHours(48)->isPast()) {
                    $requestSalary->status = 4;
                    $requestSalary->save();
                    $this->updateHostDi($host, $requestSalary->usd);
                    return [
                        'code' => 0,
                        'msg' => __('salaryTransaction::api_responses.request_expired'),
                        'status' => 422,
                        'notify' => ['host_id' => $host->id, 'type' => 0, 'amount' => $requestSalary->coins],
                    ];
                }

                if ($request->answer == 0) {
                    $requestSalary->status = 4;
                    $requestSalary->save();
                    $this->updateHostDi($host, $requestSalary->usd);
                    PendingSalaryRequest::where([
                        "user_id" => $host->id,
                        "type" => 'salary_transaction',
                    ])->delete();
                    return [
                        'code' => 1,
                        'msg' => __("salarytransaction::api_responses.edit_in_request"),
                        'data' => [],
                        'notify' => ['host_id' => $host->id, 'type' => 5, 'amount' => $requestSalary->usd],
                    ];
                }

                $requestSalary->status = 1;
                $requestSalary->save();
                return [
                    'code' => 1,
                    'msg' => __("salarytransaction::api_responses.edit_in_request"),
                    'data' => [],
                ];
            });
        } catch (\Throwable $e) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.request_not_found'), null, 422);
        }

        if (isset($result['notify'])) {
            TransactionCustomNotification::action_request(
                $result['notify']['host_id'],
                $result['notify']['type'],
                $result['notify']['amount']
            );
        }

        return Common::apiResponse($result['code'], $result['msg'], $result['data'] ?? null, $result['status'] ?? 200);
    }

    public function updateHostDi(User $host,$coins)
    {
        // $userSalary = UserSallary::updateOrCreate([
        //     "user_id" => $host->id,
        //     "month" => date("m"),
        //     "year" => date ('Y'),
        // ],[
        //     "cut_amount" => DB::raw("cut_amount - " . $coins)
        // ]);
        $userSalary = UserSallary::where([ "user_id" => $host->id,"month" => date("m"),"year" => date("Y")]);
        if ($host->agency_id != null && $host->agency_id != 0) {
            $userSalary = $userSalary->where("user_agency_id",$host->agency_id);
        }else{
            $userSalary = $userSalary->orderByDesc("id");
        }
        $userSalary = $userSalary->first();

        if ($userSalary != null) {
            $userSalary->cut_amount -= $coins;
            $userSalary->save();
        }else{
            UserSallary::create([
                "user_id" => $host->id,"month" => date("m"),"year" => date("Y") ,'cut_amount'=> -$coins
            ]);
        }

    }

    public function transfer_salary(Request $request)
    {
        if (!$request->request_id || !$request->bill_image) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.missing_params'), null, 422);
        }
        $user = $request->user();

        // Upload before opening the transaction: keep filesystem I/O out of the DB lock window.
        $billImage = null;
        if ($request->hasFile('bill_image')) {
            $billImage = Common::upload('images', $request->file('bill_image'));
        }

        try {
            $result = DB::transaction(function () use ($request, $user, $billImage) {
                $requestSalary = SalaryRequest::with(["host", "agency"])
                    ->lockForUpdate()
                    ->find($request->request_id);

                if (!$requestSalary) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.request_not_found'), 'status' => 422];
                }
                if ($requestSalary->agency?->app_owner_id != $user->id) {
                    return ['code' => 0, 'msg' => __('api_responses.not_agency_owner'), 'status' => 422];
                }
                // Re-check status under the row lock: blocks concurrent double-transition / double-notify.
                if ($requestSalary->status != 1) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.correct_data'), 'status' => 422];
                }
                if ($billImage !== null) {
                    $requestSalary->bill_image = $billImage;
                }
                $requestSalary->status = 2;
                $requestSalary->save();

                return [
                    'code' => 1,
                    'msg' => __("salarytransaction::api_responses.edit_in_request"),
                    'data' => [],
                    'status' => 200,
                    'notify' => ['host_id' => $requestSalary->host_id, 'type' => 3, 'amount' => $requestSalary->usd],
                ];
            });
        } catch (\Throwable $e) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.request_not_found'), null, 422);
        }

        if (isset($result['notify'])) {
            TransactionCustomNotification::action_request(
                $result['notify']['host_id'],
                $result['notify']['type'],
                $result['notify']['amount']
            );
        }

        return Common::apiResponse($result['code'], $result['msg'], $result['data'] ?? null, $result['status']);
    }

    public function host_requests(Request $request)
    {
        $user = Auth::user();
        $type = request("type") ?? 0;

        $requests = SalaryRequest::where("host_id",$user->id)->where('status',$type)->paginate();

        $result = HostRequestsResource::collection($requests);
        return Common::apiResponse(1, '', $result);
    }

    public function host_action(Request $request)
    {
        if (!$request->request_id || !$request->answer) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.missing_params'), null, 422);
        }
        $user = $request->user();

        try {
            $result = DB::transaction(function () use ($request, $user) {
                $requestSalary = SalaryRequest::with(["host", "agency.owner"])
                    ->lockForUpdate()
                    ->find($request->request_id);

                if (!$requestSalary) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.request_not_found'), 'status' => 422];
                }
                if ($requestSalary->host_id != $user->id) {
                    return ['code' => 0, 'msg' => __('api_responses.not_agency_owner'), 'status' => 422];
                }
                // Re-check status under the row lock: blocks concurrent double-confirm / double-notify.
                if ($requestSalary->status != 2) {
                    return ['code' => 0, 'msg' => __('salaryTransaction::api_responses.correct_data'), 'status' => 422];
                }

                $agency_owner = $requestSalary->agency?->owner;
                $agency = $requestSalary->agency;
                if (@$agency->type != 2) {
                    return ['code' => 0, 'msg' => __('api_responses.agency_not_shipping'), 'status' => 422];
                }
                $host = $user;

                if ($request->answer == "confirm") {
                    // update status
                    $requestSalary->status = 3;
                    $requestSalary->host_check = 1;
                    // $requestSalary->request_admin_status =1;
                    //delete user pending salary
                    PendingSalaryRequest::where([
                        "user_id" => $host->id,
                        "type" => 'salary_transaction',
                    ])->delete();

                    $this->updatesalaryTransfer($agency->id, $requestSalary->usd);
                    // add salary to agency
                    // AgencySallary::updateOrCreate([
                    //     'agency_id' => $agency->id,
                    //     "month" => date("m"),
                    //     "year" => date("Y"),
                    // ],[
                    //     'sallary' => DB::raw('sallary + ' . $requestSalary->usd),
                    // ]);

                    $requestSalary->save();

                    return [
                        'code' => 1,
                        'msg' => __("salarytransaction::api_responses.edit_in_request"),
                        'data' => [],
                        'status' => 200,
                        'notify' => ['owner_id' => $agency_owner->id, 'type' => 4, 'amount' => $requestSalary->usd, 'host_id' => $requestSalary->host_id],
                    ];
                }

                // update status
                $requestSalary->status = 4;
                $requestSalary->host_check = 2;
                // add data to admin to check it
                AdminCheck::create([
                    'request_id'    =>  $requestSalary->id,
                    'admin_check'   =>  0,
                    'type'          =>  "confirmation",
                ]);
                $requestSalary->save();

                return [
                    'code' => 1,
                    'msg' => __("salarytransaction::api_responses.edit_in_request"),
                    'data' => [],
                    'status' => 200,
                    'notify' => ['owner_id' => $agency_owner->id, 'type' => 5, 'amount' => $requestSalary->usd, 'host_id' => $requestSalary->host_id],
                ];
            });
        } catch (\Throwable $e) {
            return Common::apiResponse(0, __('salaryTransaction::api_responses.request_not_found'), null, 422);
        }

        if (isset($result['notify'])) {
            TransactionCustomNotification::action_request(
                $result['notify']['owner_id'],
                $result['notify']['type'],
                $result['notify']['amount'],
                $result['notify']['host_id']
            );
        }

        return Common::apiResponse($result['code'], $result['msg'], $result['data'] ?? null, $result['status']);
    }

    public function updatesalaryTransfer($agencyId, $usd)
    {
         AgencyTransferSalary::updateOrCreate([
            'agency_id' => $agencyId,
            "month" => date("m"),
            "year" => date("Y"),
        ],[
            'salary' => DB::raw('salary + ' . $usd),
        ]);
    }


}
