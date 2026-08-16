<?php

namespace App\Admin\Controllers;

use App\Models\Agency;
use App\Models\Charge;
use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use App\Helpers\UserCommon;
use App\Models\Setting;

class chargUsersSleemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'User';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Charge());
        $grid->disableRowSelector();

        $grid->model()->where('charger_type', 'admin')->with('user')->orderByDesc('created_at');

        $grid->column('user.id', __('Id'));
        $grid->column('user.name', __('Name'));


        $grid->column('user.uuid', __('Uuid'));

        $grid->column('amount', __('coins'));
        $grid->column('balance_before', __('Balance Before'));
        $grid->column('balance_after', __('Balance'))->display(function ($value) {
            return (int)$this->balance_before + (int)$this->amount;
        });



        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(User::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('email', __('Email'));
        $show->field('email_verified_at', __('Email verified at'));
        $show->field('password', __('Password'));
        $show->field('remember_token', __('Remember token'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('phone', __('Phone'));
        $show->field('google_id', __('Google id'));
        $show->field('facebook_id', __('Facebook id'));
        $show->field('di', __('Di'));
        $show->field('coins', __('Coins'));
        $show->field('room_coins', __('Room coins'));
        $show->field('flowers', __('Flowers'));
        $show->field('flowers_value', __('Flowers value'));
        $show->field('gold', __('Gold'));
        $show->field('is_leader', __('Is leader'));
        $show->field('is_sign', __('Is sign'));
        $show->field('status', __('Status'));
        $show->field('is_points_first', __('Is points first'));
        $show->field('online_time', __('Online time'));
        $show->field('dress_1', __('Dress 1'));
        $show->field('dress_2', __('Dress 2'));
        $show->field('dress_3', __('Dress 3'));
        $show->field('dress_4', __('Dress 4'));
        $show->field('nickname', __('Nickname'));
        $show->field('mykeep', __('Mykeep'));
        $show->field('system', __('System'));
        $show->field('channel', __('Channel'));
        $show->field('img_1', __('Img 1'));
        $show->field('points', __('Points'));
        $show->field('device_token', __('Device token'));
        $show->field('scale', __('Scale'));
        $show->field('country_id', __('Country id'));
        $show->field('now_room_uid', __('Now room uid'));
        $show->field('bio', __('Bio'));
        $show->field('agency_id', __('Agency id'));
        $show->field('family_id', __('Family id'));
        $show->field('is_host', __('Is host'));
        $show->field('whatsapp', __('Whatsapp'));
        $show->field('old_usd', __('Old usd'));
        $show->field('target_usd', __('Target usd'));
        $show->field('target_token_usd', __('Target token usd'));
        $show->field('uuid', __('Uuid'));
        $show->field('is_gold_id', __('Is gold id'));
        $show->field('chat_id', __('Chat id'));
        $show->field('notification_id', __('Notification id'));
        $show->field('vip', __('Vip'));
        $show->field('sub_sender_level', __('Sub sender level'));
        $show->field('sub_receiver_level', __('Sub receiver level'));
        $show->field('sub_sender_num', __('Sub sender num'));
        $show->field('sub_receiver_num', __('Sub receiver num'));
        $show->field('salary', __('Salary'));
        $show->field('type_user', __('Type user'));
        $show->field('monthly_diamond_send', __('Monthly diamond send'));
        $show->field('total_diamond_send', __('Total diamond send'));
        $show->field('monthly_diamond_received', __('Monthly diamond received'));
        $show->field('total_diamond_received', __('Total diamond received'));
        $show->field('sender_level', __('Sender level'));
        $show->field('received_level', __('Received level'));
        $show->field('today_days', __('Today days'));
        $show->field('monthly_days', __('Monthly days'));
        $show->field('total_days', __('Total days'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Charge());
        $form->model()->with('user');
        $form->select('user.id', __('Name'))->options(function ($value) {
            if (!$value) return [];
            $user = User::find($value);
            return $user ? [$user->id => $user->uuid . '_' . $user->name] : [];
        })->ajax('/api/search/users3', 'id', 'name')->required();





        $form->number('amount', __('coins'))->required()->min(0);

        return $form;
    }

    public function store()
    {
        if (! Admin::user()->can('*')) {
            abort(403);
        }

        $data = request()->all();
        $userId = $data['user']['id'];
        $user = User::withoutAppends()->find($userId);
        $amount = $data['amount'];
        $chargerId = 1;

        $userCoins = \Cache::rememberForever('user_coins', function () {
            $setting = Setting::where('key', 'user_coins')->first();
            return $setting?->value ?? 1;
        });
        $usdAmount = $userCoins > 0 ? $amount / $userCoins : 0;

        $charge = Charge::query()->create([
            'user_id' => $userId,
            'amount' => $amount,
            'usd' => $usdAmount,
            'charger_id' => $chargerId,
            'charger_type' => 'admin',
            'user_type' => $user->type_user,
            'balance_before' => $user->di
        ]);
        $user->di += (int)$amount;
        $user->save();
        UserCommon::UserEarnedInvitation($user->id, $amount,$charge->id);
    }

    public function show($id, Content $content)
    {
        $charge = Charge::query()->where('id', $id)->with('user')->first();
        return parent::show($charge->user->id, $content); // TODO: Change the autogenerated stub
    }
}
