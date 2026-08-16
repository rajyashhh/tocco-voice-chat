<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class UsersExport implements FromView
{
    public function view(): View
    {
        $users = User::select([
            'id', 'online', 'name', 'email', 'email_verified_at',
            'created_at', 'updated_at', 'phone', 'google_id', 'huawei_id', 'facebook_id', 'di',
            'coins', 'room_coins', 'flowers', 'flowers_value', 'gold', 'is_leader', 'is_sign',
            'status', 'is_points_first', 'online_time', 'dress_1', 'dress_2',
            'dress_3', 'dress_4', 'nickname', 'mykeep', 'system',
            'channel', 'img_1', 'points', 'scale',
            'now_room_uid', 'bio', 'agency_id', 'family_id', 'is_host', 'whatsapp',
            'old_usd', 'target_usd', 'target_token_usd', 'uuid', 'is_gold_id', 'chat_id',
            'notification_id', 'vip', 'sub_sender_level', 'sub_receiver_level', 'sub_sender_num',
            'sub_receiver_num', 'salary', 'monthly_diamond_send', 'total_diamond_send',
            'total_diamond_received', 'sender_level', 'received_level',
            'type_user', 'is_manger', 'apple_id', 'today_days', 'monthly_days',
            'total_days', 'lang', 'lan', 'unread_count_message', 'country_id',
            'image_color_id', 'deleted_at', 'current_app_version', 'can_play', 'stopshow_gift',
            'manger_type_id', 'charge_status', 'android_version', 'ios_version',
            'huawei_version', 'appear_charger_agency', 'special_id', 'current_room_chat', 'game_id',
            'join_agency_date', 'transfer_salary', 'salary_is_updated', 'is_logout', 'type'
        ])->get();

        return view('admin.excel.users', compact('users'));
    }
}
