<?php

namespace Modules\SalaryTransaction\Actions;

use App\Helpers\Common;
use App\Models\AgencySallary;
use App\Models\Charge;
use App\Models\FamilyUser;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use Modules\Vip\Entities\UserVip;
use App\Models\Ware;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\SalaryTransaction\Entities\AdminCheck;
use Modules\SalaryTransaction\Entities\AgencyTransferSalary;
use Modules\SalaryTransaction\Entities\AgentSalaryRequest;
use Modules\SalaryTransaction\Entities\PendingSalaryRequest;
use Modules\SalaryTransaction\Entities\SalaryRequest;
use Modules\SalaryTransaction\Helpers\TransactionCustomNotification;

class AcceptAgentRequestAction extends RowAction
{
    public $name = 'قبول الطلب';

    public function handle(Model $model, Request $request)
    {
        $agentRequest = AgentSalaryRequest::with("agency")->find($model->id); 
        // update status in request to complete
        $agentRequest->status = 1;
        $agentRequest->save();

        $agency = $agentRequest->agency;
        if ($request -> type == 1) {
            $agency->coins += $agentRequest->coins;
            $agency->save();
        }
        // else{
        //     AgencySallary::updateOrCreate([
        //         "agency_id" => $agency->id,
        //         "month" => date("m"),
        //         "year" => date ('Y'),
        //     ],[
        //         "cut_amount" => DB::raw("cut_amount - " . $agentRequest->usd)
        //     ]);
        // }
        $salaryRequest = SalaryRequest::find($agentRequest->salary_request_id);
        if ($salaryRequest) {
            $salaryRequest->update(['request_admin_status' => 1]);
        }

        $this->updatesalaryTransfer($agentRequest->agency_id,$agentRequest->usd);
        TransactionCustomNotification::action_request($agency->app_owner_id, 6,$agentRequest->usd);
        return $this->response()->success ('تم بنجاح')->refresh();
    }

    public function form(Model $model)
    {
        $this->radio('type', __('type'))->options([
            '1' => __('coins'),
            '2' => __('usd'),
        ])  ->default($model->type);
    }

    public function updatesalaryTransfer($agencyId, $usd)
    {
         AgencyTransferSalary::updateOrCreate([
            'agency_id' => $agencyId,
            "month" => date("m"),
            "year" => date("Y"),
        ],[
            'pending_usd' => DB::raw('pending_usd - ' . $usd),
            'cut_amount' => DB::raw('cut_amount + ' . $usd),
        ]);
    }
}
