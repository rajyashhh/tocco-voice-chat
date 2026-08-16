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


class CanPlaySwitchAction extends RowAction
{
    public function name()
    {
        return $this->row->can_play == 2
            ? __('Disable Can Play')
            : __('Enable Can Play');
    }

    public function handle(Model $model)
    {
        $model->can_play = $model->can_play == 2 ? 3 : 2;
        $model->save();

        $msg = $model->can_play == 2
            ? __('Can play has been enabled!')
            : __('Can play has been disabled!');

        $response = $model->can_play == 2 ? 'success' : 'error';

        if($model->online){
            $can_play = $model->can_play ?? 0;
            $show_invite_code = $model->show_invite_code ?? 0;
            broadcast(new UserStatus(
                $can_play == 2 ? true : false,
                $show_invite_code == 1 ? true : false,
                $model->id
            ));
        }
        
        return $this->response()->$response($msg)->refresh();
    }

    public function icon()
    {
        return $this->row->can_play == 2 ? 'fa-toggle-on' : 'fa-toggle-off';
    }

    public function dialog()
    {
        $msg = $this->row->can_play == 2
            ? __('dashboard.confirm_disable_can_play')
            : __('dashboard.confirm_enable_can_play');

        $this->confirm($msg, '', []);
    }
}
