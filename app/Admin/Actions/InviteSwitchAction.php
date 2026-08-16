<?php

namespace App\Admin\Actions;

use App\Events\UserStatus;
use App\Facades\CustomNotification;
use App\Models\Ban;
use App\Models\User;
use Encore\Admin\Actions\Action;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;


class InviteSwitchAction extends RowAction
{

    public function name()
    {
        return @$this->row->userSetting->show_invite_code
            ? __('Disable Show Invite Code')
            : __('Enable Show Invite Code');
    }

    public function handle(Model $model)
    {
        $userSetting = $model->userSetting;

        $userSetting->show_invite_code = !$userSetting->show_invite_code;
        $userSetting->save();

        $statusMsg = $userSetting->show_invite_code
            ? __('Show invite code has been enabled!')
            : __('Show invite code has been disabled!');

        $response = $userSetting->show_invite_code ? 'success' : 'error';

        return $this->response()->$response($statusMsg)->refresh();
    }

    public function icon()
    {
        $userSetting = @$this->row->userSetting;
        return ($userSetting && $userSetting->show_invite_code) ? 'fa-toggle-on' : 'fa-toggle-off';
    }

    public function dialog()
    {
        $msg = @$this->row->userSetting->show_invite_code
            ? __('dashboard.confirm_disable_invite_code')
            : __('dashboard.confirm_enable_invite_code');

        $this->confirm($msg, '', []);
    }
}
