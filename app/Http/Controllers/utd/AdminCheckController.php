<?php

namespace App\Http\Controllers\utd;

use Exception;
use App\Models\User;
use App\Helpers\Common;
use App\Models\UserSallary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminCheckResource;
use App\Http\Resources\AgencyRequestsResource;
use Modules\SalaryTransaction\Entities\AdminCheck;
use Modules\SalaryTransaction\Entities\AgencyTransferSalary;
use Modules\SalaryTransaction\Entities\PendingSalaryRequest;
use Modules\SalaryTransaction\Helpers\TransactionCustomNotification;


class AdminCheckController extends Controller
{

    public function all(Request $request)
    {
        $id = $request->id;
        $page = $request->page;
        $perPage = $request->per_page;
        try {
            $data = AdminCheck::where("admin_check", '!=', 1)->with('request')->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($perPage, ['*'], 'page', $page);
            return Common::apiResponse(true, 'success', AdminCheckResource::collection($data));
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function accept($id)
    {
        $adminCheck = AdminCheck::find($id);
        $requestSalary = $adminCheck->request;
        // update status in request to complete
        $requestSalary->request_admin_status = 2;
        $requestSalary->status = 3;
        $requestSalary->save();
        // update status in admin check to checked
        $adminCheck->admin_check = 1;
        $adminCheck->save();

        // delete pending process
        PendingSalaryRequest::where([
            "user_id" => $requestSalary->host_id,
            "type" => 'salary_transaction',
        ])->delete();
        // update 
        $this->updateSalaryTransfer($requestSalary->agency_id, $requestSalary->usd);
        TransactionCustomNotification::action_request($requestSalary->host_id, 0, $requestSalary->usd);
        TransactionCustomNotification::action_request($requestSalary?->agency?->app_owner_id, 0, $requestSalary->usd);
        return Common::apiResponse(1, 'accepted',  200);
    }

    public function cancel($id)
    {

        $adminCheck = AdminCheck::find($id);
        $request = $adminCheck->request;
        // update status in request to complete
        $request->status = 4;
        $request->request_admin_status = 1;
        $request->save();

        // update status in admin check to checked
        $adminCheck->admin_check = 1;
        $adminCheck->save();

        // delete pending process
        $pending = PendingSalaryRequest::where([
            "user_id" => $request->host_id,
            "type" => 'salary_transaction',
        ])->first();

        $host = User::find($request->host_id);
        // return to host dollars
        $userSalary = UserSallary::where(["user_id" => $host->id, "month" => date("m"), "year" => date("Y")]);
        if ($host->agency_id != null && $host->agency_id != 0) {
            $userSalary = $userSalary->where("user_agency_id", $host->agency_id);
        } else {
            $userSalary = $userSalary->orderByDesc("id");
        }
        $userSalary = $userSalary->first();

        if ($userSalary != null) {
            $userSalary->cut_amount -= $pending->salary;
            $userSalary->save();
        } else {
            UserSallary::create([
                "user_id" => $host->id,
                "month" => date("m"),
                "year" => date("Y"),
                'cut_amount' => -$pending->salary
            ]);
        }

        $pending->delete();
        TransactionCustomNotification::action_request($request->host_id, 5, $request->usd);
        TransactionCustomNotification::action_request($request?->agency?->app_owner_id, 5, $request->usd);
        return Common::apiResponse(1, 'refused',  200);
    }

    public function updateSalaryTransfer($agencyId, $usd)
    {
        AgencyTransferSalary::updateOrCreate([
            'agency_id' => $agencyId,
            "month" => date("m"),
            "year" => date("Y"),
        ], [
            'salary' => DB::raw('salary + ' . $usd),
        ]);
    }
}
