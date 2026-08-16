<?php

namespace App\Admin\Actions;

use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Models\AgencyJoinRequest;
use App\Models\FamilyUser;
use App\Models\User;
use App\Models\UserSallary;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;


class ResetUserSalary extends RowAction
{
    public $name;
    public function __construct($id = 0)
    {
        $this->name = __("reset salary");
        parent::__construct();
    }
    public function handle(Model $model, Request $request)
    {
        $user = User::find($model->id);
        $totalSalary = UserSallary::where('user_id', $user->id)
            ->sum(DB::raw('sallary - cut_amount'));

        // If already zero, do nothing
        if ($totalSalary == 0) {
            return $this->response()->success(__('salary already zero'));
        }

        // Get the last salary record (most recent)
        $lastSalary = UserSallary::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (!$lastSalary) {
            return $this->response()->error(__('no salary record'));
        }

        $lastSalary->cut_amount += $totalSalary;
        $lastSalary->save();

        return $this->response()->success(__('dashboard.successful'))->refresh();
    }

    public function dialog()
    {
        $this->confirm(__("admin.resetSalary"), '', []);
    }
}
