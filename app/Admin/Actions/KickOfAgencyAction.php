<?php

namespace App\Admin\Actions;

use App\Facades\UserHandling;
use App\Helpers\Common;
use App\Models\AgencyJoinRequest;
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
use Illuminate\Validation\ValidationException;

class KickOfAgencyAction extends RowAction
{
    public $name;
    public function __construct($id = 0)
    {
        $this->name = __("dashboard.kickAgency");
        parent::__construct();
    }
    public function handle(Model $model, Request $request)
    {
        if (UserHandling::checkIfUserOwnerOfAgency($model)){
            throw ValidationException::withMessages(['error' => __('This user is the agency owner and cannot be deleted')]);
        }

        UserHandling::kickUserFromAgency($model);
//        $model->save ();
        return $this->response()->success (__('dashboard.successful'));
    }

    public function dialog()
    {
        $this->confirm(__("dashboard.chickKickAgency"),'',[]);
    }
}
