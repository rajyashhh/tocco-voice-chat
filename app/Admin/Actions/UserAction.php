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


class UserAction extends Action
{
    public $id;
    public $charge_status;
    public $transfer_salary;
    public $show_invite_code;
    public $hide_chat;
    public $can_play;
    public $permission_name = 'user-status';


    protected $selector = '.delete-ban';

    public function __construct($id = 0, $charge_status = 0, $transfer_salary = 0, $show_invite_code = 0,
    $hide_chat = 0,
    $can_play = 3)
    {

        if ($can_play == 0) {
            $can_play = 3;
        } elseif ($can_play == 1) {
            $can_play = 2;
        }
        $this->id = $id;
        $this->charge_status = $charge_status;
        $this->transfer_salary = $transfer_salary;
        $this->show_invite_code = $show_invite_code;
        $this->hide_chat = $hide_chat;
        $this->can_play = $can_play;


        parent::__construct();
    }

    public function handle(\Illuminate\Http\Request $request)
    {

        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }

        $user = User::find($request->id);
        if (!$user) {
            return $this->response()->error(__('user not found'))->refresh();
        }

        if($user->online){
            $can_play = $request->can_play ?? 0;
            $show_invite_code = $request->show_invite_code ?? 0;
            broadcast(new UserStatus(
                $can_play == 2 ? true : false,
                $show_invite_code == 1 ? true : false,
                $request->id
            ));
        }


        $user->update([
            'charge_status'   => $request->charge_status,
            'transfer_salary'  => $request->transfer_salary,
            'can_play' => $request->can_play,
        ]);
        $user->userSetting()->update(
            [
                'show_invite_code'  => $request->show_invite_code,
                'hide_chat' => $request->hide_chat,
            ]

        );
        return $this->response()->success('success')->refresh();
    }


    public function form()
    {
        $this->hidden('id', __('ID'))->attribute('id', 'id');

        $this->hidden('charge_status')->default(1);
//        $this->radio('charge_status', __('Charge Status'))
//            ->options([1 => __('on'), 0 => __('off')])
//            ->value($this->charge_status);

        $this->radio('transfer_salary', __('Transfer Salary'))
            ->options([1 => __('on'), 0 => __('off')])->value($this->transfer_salary);

//        $this->radio('show_invite_code', __('Show Invite Code'))
//            ->options([1 => __('on'), 0 => __('off')])->value($this->show_invite_code);
//
//            $this->hidden('hide_chat', __('ID'))->attribute('hide_chat', 'id');

        /* $this->radio('hide_chat', __('Hide Chat'))
            ->options([1 => __('on'), 0 => __('off')])->value($this->hide_chat); */
//            $this->hidden('hide_chat')->default(0); // false == 0
//
        $this->hidden('can_play')->default(0); // false == 0
//        $this->radio('can_play', __('Can Play'))
//            ->options([2 => __('yes'), 3 => __('no')])->value($this->can_play);
    }



    public function html()
    {
        return '<a href="#" onclick="openUserForm(' .
            '\'' . $this->id . '\', ' .
//            '\'' . $this->charge_status . '\', ' .
            '\'' . $this->transfer_salary . '\', ' .
//            '\'' . $this->show_invite_code . '\', ' .
//            '\'' . $this->hide_chat . '\', ' .
//            '\'' . $this->can_play . '\'' .
            ')" class="btn btn-sm btn-info delete-ban">
            <i class="fa fa-edit"></i> ' . ' '. __('status') . '
        </a>
        <script>
            function openUserForm(id, charge_status, transfer_salary, show_invite_code, hide_chat, can_play) {
                console.log(id, charge_status, transfer_salary, show_invite_code, hide_chat, can_play);

                $("#id").val(id);
//                $("#charge_status").val(charge_status);
                $("#transfer_salary").val(transfer_salary);
//                $("#show_invite_code").val(show_invite_code);
//                $("#hide_chat").val(hide_chat);
//                $("#can_play").val(can_play);
            }
        </script>';
    }
}
