<?php

namespace Modules\RoleRewards\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Facades\Admin;

class DeleteRole extends RowAction
{
    public function name(): string
    {
        return __('Delete'); 
    }

    public function handle(Model $model)
    {
        if (in_array($model->slug, \App\Admin\Controllers\RoleControllerNew::PORTAL_ROLE_SLUGS, true)) {
            return $this->response()->error(__('This role is a position system role and is managed automatically'));
        }

            \Modules\RoleRewards\Helpers\UserRoleRewardHelper::revokeRewardsFromAllUsersForRole(
                $model->id,
                $model->slug
            );
      
        $model->delete();

        return $this->response()->success('Deleted successfully')->refresh();
    }

    public function dialog()
    {
        $this->confirm(__('dashboard.chickDelete'), '', []);
    }
}
