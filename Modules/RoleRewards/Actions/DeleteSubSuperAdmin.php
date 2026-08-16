<?php

namespace Modules\RoleRewards\Actions;

use App\Models\User;
use App\Models\Agency;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Modules\RoleRewards\Helpers\UserRoleRewardHelper;

class DeleteSubSuperAdmin extends RowAction
{
    public function name(): string
    {
        return __('Delete');
    }

    public function handle(Model $model)
    {


        // if ($model) {
        //     if ($model->isRole('admin') || $model->isRole('developer')) {
        //         return $this->response()->error(__('admin cant be deleted'))->refresh();
        //     }
        // }

        $OldUserAppId = User::find($model->app_id);
        if ($OldUserAppId) {
            $OldUserAppId->is_sub_super_admin = 0;
            $OldUserAppId->save();
        }



        $model->delete();

        return $this->response()->success('Deleted successfully')->refresh();
    }
}
