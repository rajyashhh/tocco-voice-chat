<?php

namespace Modules\SalaryTransaction\Actions;

use App\Helpers\Common;
use App\Models\AgencySallary;
use App\Models\FamilyUser;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use App\Models\UserSallary;
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
use Modules\SalaryTransaction\Helpers\TransactionCustomNotification;

class RejectedAgentRequestAction extends RowAction
{
    public $name = 'الغاء الطلب';

    public function handle(Model $model, Request $request)
    {
        $request = AgentSalaryRequest::find($model->id); 

        // update status in request to complete
        $request->status = 3;
        $request->save();

        // return to host dollars
        $userSalary = AgencySallary::firstOrNew([
            "agency_id" => $request->agency_id,
            "month" => date("m"),
            "year" => date("Y"),
        ]);
        
        if ($userSalary->exists) {
            $userSalary->decrement('cut_amount', $request->usd);
        } else {
            $userSalary->cut_amount = - $request->usd;
            $userSalary->save();
        }
        $this->updatesalaryTransfer($request->agency_id,$request->usd);
        TransactionCustomNotification::action_request($request?->agency?->app_owner_id, 6,$request->usd);

        return $this->response()->success ('تم بنجاح')->refresh();
    }

    public function dialog()
    {
        $this->confirm('هل متاكد من انك تريد رفض الطلب ؟','',[]);
    }

    public function updatesalaryTransfer($agencyId, $usd)
    {
         AgencyTransferSalary::updateOrCreate([
            'agency_id' => $agencyId,
            "month" => date("m"),
            "year" => date("Y"),
        ],[
            'pending_usd' => DB::raw('pending_usd - ' . $usd),
        ]);
    }
}
