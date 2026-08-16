<?php

namespace Modules\Country\Actions\Admin;

use App\Models\Bd;
use App\Models\User;
use Modules\Country\Entities\SubAdmin;
use Modules\Country\Entities\SuperAdmin;
use Illuminate\Http\Request;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class DeleteSuperAdminsAction extends RowAction
{
    public $name;

    protected $agencyCount = 0;

    public function __construct()
    {
        parent::__construct();
        $this->name = __('delete');
    }

    public function setModel(Model $model)
    {
        //            $this->agencyCount = Agency::where('bd_id', $model->app_id)->count();
        return parent::setModel($model);
    }


    public function handle(Model $model, Request $request)
    {

        if ($model->default == 1) $this->response()->error(__('can not delete default super admin'))->refresh();
        if ($model->country_id == 0 ) $this->response()->error(__('can not delete default super admin'))->refresh();
        $user = User::find($model->app_id);

        if ($user) {
            $user->is_super_admin = 0;
            $user->save();
        }
        $defaultSuperAdmin = SuperAdmin::where('default', 1)->first();
        if ($defaultSuperAdmin) Bd::where('parent_id', $model->id)->update(['parent_id' => $defaultSuperAdmin->id]);

        $subAdmins =  SubAdmin::where('parent_id', $model->id)->pluck('app_id')->toArray();
        if (!empty($subAdmins)) {
            User::whereIn('id', $subAdmins)->update(['sub_area_manger' => 0]);
            SubAdmin::where('parent_id', $model->id)->delete();
        }
        $model->delete();

        return $this->response()->success(__('super admin deleted successfully.'))->refresh();
    }


    public function dialog()
    {
        $this->confirm(__('dashboard.chickDelete'), '', []);
    }

    public function getHandleRoute()
    {
        return url(request()->segment(1) . '/_handle_action_');
    }
}
