<?php

namespace Modules\SalaryTransaction\Actions;

use App\Helpers\Common;
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
use Modules\SalaryTransaction\Entities\PendingSalaryRequest;
use Modules\SalaryTransaction\Entities\SalaryRequest;
use Modules\SalaryTransaction\Helpers\TransactionCustomNotification;

class AccepRequestAction extends RowAction
{
    public $name = 'قبول الطلب';

    public function handle(Model $model, Request $request)
    {
        $adminCheck = AdminCheck::find($model->id); 
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
        $this->updatesalaryTransfer($requestSalary->agency_id,$requestSalary->usd);
        TransactionCustomNotification::action_request($requestSalary->host_id, 0,$requestSalary->usd);
        TransactionCustomNotification::action_request($requestSalary?->agency?->app_owner_id, 0,$requestSalary->usd);
        return $this->response()->success ('تم بنجاح')->refresh();
    }

    public function dialog()
    {
        $this->confirm('هل متاكد من انك تريد تتم الطلب ؟','',[]);
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
