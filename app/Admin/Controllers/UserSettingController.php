<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use Encore\Admin\Layout\Row;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Box;

class UserSettingController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'user setting';
    public $permission_name = 'user-setting';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-users');
        }

        $content = $content->title(__($this->title));

        // Conditionally add the first row
        if (Admin::user()->can('actions-switch' . $this->permission_name) || Admin::user()->can('*')) {
            $content = $content->row(function (Row $row) {
                $row->column(12, $this->grid2());
            });
        }



        return $content;
    }


    protected function grid2()
    {
        $transfer_salary = settings()->get('transfer_salary');
        $stop_invite_code = settings()->get('stop_invite_code');
        $stop_charge = Common::getSettingValue('stop_charge') ?? 0;
        $make_rooms_top = settings()->get('make_rooms_top');
        $make_gift_top = settings()->get('close_open_gifts');
        $change_country = settings()->get('change_country');
        $register_account = Common::getSettingValue('register_account') ?? 0;


        return (new Box(
            title: __('admin.Actions'),
            content: view('admin.grid.users.userChargeViewNew', compact(['stop_charge', 'change_country', 'make_rooms_top', 'stop_invite_code', 'transfer_salary', 'make_gift_top', 'register_account'])),
        ));
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
        $show->field('online', __('Online'));
        $show->field('name', __('Name'));
        $show->field('email', __('Email'));
        $show->field('email_verified_at', __('Email verified at'));
        $show->field('password', __('Password'));
        $show->field('remember_token', __('Remember token'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('phone', __('Phone'));
        $show->field('google_id', __('Google id'));
        $show->field('huawei_id', __('Huawei id'));
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
        $show->field('monthly_diamond_send', __('Monthly diamond send'));
        $show->field('total_diamond_send', __('Total diamond send'));
        $show->field('monthly_diamond_received', __('Monthly diamond received'));
        $show->field('exchange_diamonds', __('Exchange diamonds'));
        $show->field('total_diamond_received', __('Total diamond received'));
        $show->field('sender_level', __('Sender level'));
        $show->field('received_level', __('Received level'));
        $show->field('type_user', __('Type user'));
        $show->field('is_manger', __('Is manger'));
        $show->field('apple_id', __('Apple id'));
        $show->field('today_days', __('Today days'));
        $show->field('monthly_days', __('Monthly days'));
        $show->field('total_days', __('Total days'));
        $show->field('lang', __('Lang'));
        $show->field('lan', __('Lan'));
        $show->field('unread_count_message', __('Unread count message'));
        $show->field('country_id', __('Country id'));
        $show->field('image_color_id', __('Image color id'));
        $show->field('deleted_at', __('Deleted at'));
        $show->field('current_app_version', __('Current app version'));
        $show->field('can_play', __('Can play'));
        $show->field('stopshow_gift', __('Stopshow gift'));
        $show->field('manger_type_id', __('Manger type id'));
        $show->field('charge_status', __('Charge status'));
        $show->field('android_version', __('Android version'));
        $show->field('ios_version', __('Ios version'));
        $show->field('huawei_version', __('Huawei version'));
        $show->field('appear_charger_agency', __('Appear charger agency'));
        $show->field('special_id', __('Special id'));
        $show->field('current_room_chat', __('Current room chat'));
        $show->field('game_id', __('Game id'));
        $show->field('transfer_salary', __('Transfer salary'));
        $show->field('join_agency_date', __('Join agency date'));
        $show->field('type', __('Type'));
        $show->field('salary_is_updated', __('Salary is updated'));
        $show->field('is_logout', __('Is logout'));
        $show->field('lat', __('Lat'));
        $show->field('long', __('Long'));
        $show->field('total_points', __('Total points'));
        $show->field('moment_type', __('Moment type'));
        $show->field('anonymous_id', __('Anonymous id'));
        $show->field('is_anonymous', __('Is anonymous'));
        $show->field('is_active', __('Is active'));
        $show->field('total_charge_coins', __('Total charge coins'));
        $show->field('charge_level', __('Charge level'));
        $show->field('color_id', __('Color id'));
        $show->field('number_of_followings', __('Following'));
        $show->field('number_of_fans', __('Follower'));
        $show->field('number_of_friends', __('Friend'));
        $show->field('new_gift', __('New gift'));
        $show->field('sub_charger_level', __('Sub charger level'));
        $show->field('sub_charger_coins', __('Sub charger coins'));
        $show->field('profile_count', __('Profile count'));
        $show->field('exchange_coins', __('Exchange coins'));
        $show->field('is_bd', __('Is bd'));

        return $show;
    }

    public function store()
    {
        Permission::check('create-' . $this->permission_name);

        return parent::store();
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new User());

        $form->switch('online', __('Online'));
        $form->text('name', __('Name'));
        $form->email('email', __('Email'));
        $form->datetime('email_verified_at', __('Email verified at'))->default(date('Y-m-d H:i:s'));
        $form->password('password', __('Password'));
        $form->text('remember_token', __('Remember token'));
        $form->mobile('phone', __('Phone'));
        $form->text('google_id', __('Google id'));
        $form->textarea('huawei_id', __('Huawei id'));
        $form->text('facebook_id', __('Facebook id'));
        $form->decimal('di', __('Di'));
        $form->decimal('coins', __('Coins'));
        $form->decimal('room_coins', __('Room coins'));
        $form->decimal('flowers', __('Flowers'));
        $form->decimal('flowers_value', __('Flowers value'));
        $form->decimal('gold', __('Gold'));
        $form->switch('is_leader', __('Is leader'));
        $form->switch('is_sign', __('Is sign'));
        $form->switch('status', __('Status'))->default(1);
        $form->switch('is_points_first', __('Is points first'));
        $form->number('online_time', __('Online time'));
        $form->number('dress_1', __('Dress 1'));
        $form->number('dress_2', __('Dress 2'));
        $form->number('dress_3', __('Dress 3'));
        $form->number('dress_4', __('Dress 4'));
        $form->text('nickname', __('Nickname'));
        $form->text('mykeep', __('Mykeep'));
        $form->text('system', __('System'))->default('normal');
        $form->text('channel', __('Channel'))->default('normal');
        $form->text('img_1', __('Img 1'));
        $form->number('points', __('Points'));
        $form->text('device_token', __('Device token'));
        $form->number('scale', __('Scale'));
        $form->number('now_room_uid', __('Now room uid'));
        $form->textarea('bio', __('Bio'));
        $form->number('agency_id', __('Agency id'));
        $form->number('family_id', __('Family id'));
        $form->switch('is_host', __('Is host'));
        $form->text('whatsapp', __('Whatsapp'));
        $form->decimal('old_usd', __('Old usd'));
        $form->decimal('target_usd', __('Target usd'));
        $form->decimal('target_token_usd', __('Target token usd'));
        $form->text('uuid', __('Uuid'));
        $form->switch('is_gold_id', __('Is gold id'));
        $form->text('chat_id', __('Chat id'));
        $form->text('notification_id', __('Notification id'));
        $form->number('vip', __('Vip'));
        $form->number('sub_sender_level', __('Sub sender level'));
        $form->number('sub_receiver_level', __('Sub receiver level'));
        $form->number('sub_sender_num', __('Sub sender num'));
        $form->number('sub_receiver_num', __('Sub receiver num'));
        $form->decimal('salary', __('Salary'))->default(0.00);
        $form->number('monthly_diamond_send', __('Monthly diamond send'));
        $form->number('total_diamond_send', __('Total diamond send'));
        $form->number('monthly_diamond_received', __('Monthly diamond received'));
        $form->decimal('exchange_diamonds', __('Exchange diamonds'));
        $form->number('total_diamond_received', __('Total diamond received'));
        $form->number('sender_level', __('Sender level'));
        $form->number('received_level', __('Received level'));
        $form->number('type_user', __('Type user'));
        $form->switch('is_manger', __('Is manger'));
        $form->text('apple_id', __('Apple id'));
        $form->number('today_days', __('Today days'));
        $form->number('monthly_days', __('Monthly days'));
        $form->number('total_days', __('Total days'));
        $form->text('lang', __('Lang'))->default('en');
        $form->text('lan', __('Lan'))->default('en');
        $form->number('unread_count_message', __('Unread count message'));
        $form->number('country_id', __('Country id'));
        $form->number('image_color_id', __('Image color id'));
        $form->number('current_app_version', __('Current app version'));
        $form->number('can_play', __('Can play'));
        $form->switch('stopshow_gift', __('Stopshow gift'));
        $form->number('manger_type_id', __('Manger type id'));
        $form->number('charge_status', __('Charge status'))->default(1);
        $form->number('android_version', __('Android version'));
        $form->number('ios_version', __('Ios version'));
        $form->number('huawei_version', __('Huawei version'));
        $form->switch('appear_charger_agency', __('Appear charger agency'))->default(1);
        $form->text('special_id', __('Special id'));
        $form->number('current_room_chat', __('Current room chat'));
        $form->number('game_id', __('Game id'));
        $form->switch('transfer_salary', __('Transfer salary'));
        $form->text('join_agency_date', __('Join agency date'));
        $form->text('type', __('Type'))->default('app');
        $form->switch('salary_is_updated', __('Salary is updated'));
        $form->number('is_logout', __('Is logout'))->default(1);
        $form->text('lat', __('Lat'));
        $form->text('long', __('Long'));
        $form->number('total_points', __('Total points'));
        $form->text('moment_type', __('Moment type'));
        $form->number('anonymous_id', __('Anonymous id'));
        $form->switch('is_anonymous', __('Is anonymous'));
        $form->switch('is_active', __('Is active'));
        $form->number('total_charge_coins', __('Total charge coins'));
        $form->number('charge_level', __('Charge level'));
        $form->text('color_id', __('Color id'));
        $form->switch('new_gift', __('New gift'))->default(1);
        $form->number('sub_charger_level', __('Sub charger level'));
        $form->number('sub_charger_coins', __('Sub charger coins'));
        $form->number('profile_count', __('Profile count'));
        $form->number('exchange_coins', __('Exchange coins'));
        $form->switch('is_bd', __('Is bd'));

        return $form;
    }
}
