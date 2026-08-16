<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Users</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Online</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Email Verified At</th>
                    <th>Password</th>
                    <th>Remember Token</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Phone</th>
                    <th>Google ID</th>
                    <th>Huawei ID</th>
                    <th>Facebook ID</th>
                    <th>DI</th>
                    <th>Coins</th>
                    <th>Room Coins</th>
                    <th>Flowers</th>
                    <th>Flowers Value</th>
                    <th>Gold</th>
                    <th>Is Leader</th>
                    <th>Is Sign</th>
                    <th>Status</th>
                    <th>Is Points First</th>
                    <th>Online Time</th>
                    <th>Dress 1</th>
                    <th>Dress 2</th>
                    <th>Dress 3</th>
                    <th>Dress 4</th>
                    <th>Nickname</th>
                    <th>My Keep</th>
                    <th>System</th>
                    <th>Channel</th>
                    <th>Image 1</th>
                    <th>Points</th>
                    <th>Device Token</th>
                    <th>Scale</th>
                    <th>Now Room UID</th>
                    <th>Bio</th>
                    <th>Agency ID</th>
                    <th>Family ID</th>
                    <th>Is Host</th>
                    <th>WhatsApp</th>
                    <th>Old USD</th>
                    <th>Target USD</th>
                    <th>Target Token USD</th>
                    <th>UUID</th>
                    <th>Is Gold ID</th>
                    <th>Chat ID</th>
                    <th>Notification ID</th>
                    <th>VIP</th>
                    <th>Sub Sender Level</th>
                    <th>Sub Receiver Level</th>
                    <th>Sub Sender Num</th>
                    <th>Sub Receiver Num</th>
                    <th>Salary</th>
                    <th>Monthly Diamond Send</th>
                    <th>Total Diamond Send</th>
                    <th>Monthly Diamond Received</th>
                    <th>Total Diamond Received</th>
                    <th>Sender Level</th>
                    <th>Received Level</th>
                    <th>Type User</th>
                    <th>Is Manager</th>
                    <th>Apple ID</th>
                    <th>Today Days</th>
                    <th>Monthly Days</th>
                    <th>Total Days</th>
                    <th>Lang</th>
                    <th>LAN</th>
                    <th>Auth Token</th>
                    <th>Unread Count Message</th>
                    <th>Country ID</th>
                    <th>Image Color ID</th>
                    <th>Deleted At</th>
                    <th>Current App Version</th>
                    <th>Can Play</th>
                    <th>Stopshow Gift</th>
                    <th>Manager Type ID</th>
                    <th>Charge Status</th>
                    <th>Android Version</th>
                    <th>iOS Version</th>
                    <th>Huawei Version</th>
                    <th>Appear Charger Agency</th>
                    <th>Special ID</th>
                    <th>Current Room Chat</th>
                    <th>Game ID</th>
                    <th>Join Agency Date</th>
                    <th>Transfer Salary</th>
                    <th>Salary Is Updated</th>
                    <th>Is Logout</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->online }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->email_verified_at }}</td>
                        <td>{{ $user->password }}</td>
                        <td>{{ $user->remember_token }}</td>
                        <td>{{ $user->created_at }}</td>
                        <td>{{ $user->updated_at }}</td>
                        <td>{{ $user->phone }}</td>
                        <td>{{ $user->google_id }}</td>
                        <td>{{ $user->huawei_id }}</td>
                        <td>{{ $user->facebook_id }}</td>
                        <td>{{ $user->di }}</td>
                        <td>{{ $user->coins }}</td>
                        <td>{{ $user->room_coins }}</td>
                        <td>{{ $user->flowers }}</td>
                        <td>{{ $user->flowers_value }}</td>
                        <td>{{ $user->gold }}</td>
                        <td>{{ $user->is_leader }}</td>
                        <td>{{ $user->is_sign }}</td>
                        <td>{{ $user->status }}</td>
                        <td>{{ $user->is_points_first }}</td>
                        <td>{{ $user->online_time }}</td>
                        <td>{{ $user->dress_1 }}</td>
                        <td>{{ $user->dress_2 }}</td>
                        <td>{{ $user->dress_3 }}</td>
                        <td>{{ $user->dress_4 }}</td>
                        <td>{{ $user->nickname }}</td>
                        <td>{{ $user->mykeep }}</td>
                        <td>{{ $user->system }}</td>
                        <td>{{ $user->channel }}</td>
                        <td>{{ $user->img_1 }}</td>
                        <td>{{ $user->points }}</td>
                        <td>{{ $user->device_token }}</td>
                        <td>{{ $user->scale }}</td>
                        <td>{{ $user->now_room_uid }}</td>
                        <td>{{ $user->bio }}</td>
                        <td>{{ $user->agency_id }}</td>
                        <td>{{ $user->family_id }}</td>
                        <td>{{ $user->is_host }}</td>
                        <td>{{ $user->whatsapp }}</td>
                        <td>{{ $user->old_usd }}</td>
                        <td>{{ $user->target_usd }}</td>
                        <td>{{ $user->target_token_usd }}</td>
                        <td>{{ $user->uuid }}</td>
                        <td>{{ $user->is_gold_id }}</td>
                        <td>{{ $user->chat_id }}</td>
                        <td>{{ $user->notification_id }}</td>
                        <td>{{ $user->vip }}</td>
                        <td>{{ $user->sub_sender_level }}</td>
                        <td>{{ $user->sub_receiver_level }}</td>
                        <td>{{ $user->sub_sender_num }}</td>
                        <td>{{ $user->sub_receiver_num }}</td>
                        <td>{{ $user->salary }}</td>
                        <td>{{ $user->monthly_diamond_send }}</td>
                        <td>{{ $user->total_diamond_send }}</td>
                        <td>{{ $user->monthly_diamond_received }}</td>
                        <td>{{ $user->total_diamond_received }}</td>
                        <td>{{ $user->sender_level }}</td>
                        <td>{{ $user->received_level }}</td>
                        <td>{{ $user->type_user }}</td>
                        <td>{{ $user->is_manger }}</td>
                        <td>{{ $user->apple_id }}</td>
                        <td>{{ $user->today_days }}</td>
                        <td>{{ $user->monthly_days }}</td>
                        <td>{{ $user->total_days }}</td>
                        <td>{{ $user->lang }}</td>
                        <td>{{ $user->lan }}</td>
                        <td>{{ $user->auth_token }}</td>
                        <td>{{ $user->unread_count_message }}</td>
                        <td>{{ $user->country_id }}</td>
                        <td>{{ $user->image_color_id }}</td>
                        <td>{{ $user->deleted_at }}</td>
                        <td>{{ $user->current_app_version }}</td>
                        <td>{{ $user->can_play }}</td>
                        <td>{{ $user->stopshow_gift }}</td>
                        <td>{{ $user->manger_type_id }}</td>
                        <td>{{ $user->charge_status }}</td>
                        <td>{{ $user->android_version }}</td>
                        <td>{{ $user->ios_version }}</td>
                        <td>{{ $user->huawei_version }}</td>
                        <td>{{ $user->appear_charger_agency }}</td>
                        <td>{{ $user->special_id }}</td>
                        <td>{{ $user->current_room_chat }}</td>
                        <td>{{ $user->game_id }}</td>
                        <td>{{ $user->join_agency_date }}</td>
                        <td>{{ $user->transfer_salary }}</td>
                        <td>{{ $user->salary_is_updated }}</td>
                        <td>{{ $user->is_logout }}</td>
                        <td>{{ $user->type }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
