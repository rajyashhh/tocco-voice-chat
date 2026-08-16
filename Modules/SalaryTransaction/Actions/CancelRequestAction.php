<?php

namespace Modules\SalaryTransaction\Actions;

use App\Helpers\Common;
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
use Modules\SalaryTransaction\Entities\PendingSalaryRequest;
use Modules\SalaryTransaction\Helpers\TransactionCustomNotification;

class CancelRequestAction extends RowAction
{
    public $name = 'الغاء الطلب';

    public function handle(Model $model, Request $request)
    {
        $adminCheck = AdminCheck::find($model->id); 
        $request = $adminCheck->request;
        // update status in request to complete
        $request->status = 4;
        $request->request_admin_status = 1;
        $request->save();

        // update status in admin check to checked
        $adminCheck->admin_check = 1;
        $adminCheck->save();

        // delete pending process
       $pending= PendingSalaryRequest::where([
            "user_id" => $request->host_id,
            "type" => 'salary_transaction',
        ])->first();

        $host = User::find($request->host_id);
        // return to host dollars
        $userSalary = UserSallary::where([ "user_id" => $host->id,"month" => date("m"),"year" => date("Y")]);
        if ($host->agency_id != null && $host->agency_id != 0) {
            $userSalary = $userSalary->where("user_agency_id",$host->agency_id);
        }else{
            $userSalary = $userSalary->orderByDesc("id");
        }
        $userSalary = $userSalary->first();

        if ($userSalary != null) {
            $userSalary->cut_amount -= $pending->salary;
            $userSalary->save();
        }else{
            UserSallary::create([
                "user_id" => $host->id,"month" => date("m"),"year" => date("Y") ,'cut_amount' => -$pending->salary
            ]);
        }

        // $userSalary = UserSallary::firstOrNew([
        //     "user_id" => $request->host_id,
        //     "month" => date("m"),
        //     "year" => date("Y"),
        //     "user_agency_id" => $host->agency_id,
        // ]);
        
        // if ($userSalary->exists) {
        //     $userSalary->decrement('cut_amount', $pending->salary);
        // } else {
        //     $userSalary->cut_amount = - $pending->salary;
        //     $userSalary->save();
        // }

        $pending->delete();
        TransactionCustomNotification::action_request($request->host_id, 5,$request->usd);
        TransactionCustomNotification::action_request($request?->agency?->app_owner_id, 5,$request->usd);
        return $this->response()->success ('تم بنجاح')->refresh();
    }

    public function dialog()
    {
        $this->confirm('هل متاكد من انك تريد تتم الطلب ؟','',[]);
    }
}
