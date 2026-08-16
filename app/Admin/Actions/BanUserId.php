<?php

namespace App\Admin\Actions;

use App\Models\Ban;
use App\Models\Room;
use App\Models\User;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use App\Helpers\Common;
use App\Models\CoinLog;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Auth;

class BanUserId extends Action
{
    public $name;

    protected $selector = '.ban_user_action';

    public function handle(Request $request)
    {

        $user = User::query ()->where ('id',$request->uid)->first ();

        $room = Room::query ()->where('uid',@$user->now_room_uid??0)->first();
        if (!$user){
            return $this->response()->error(__('user not found'))->refresh();
        }
        if (in_array ('ip',$request->type)){
            $ips = $user->ips;
            foreach ($ips as $ip){
                Ban::query ()->create (
                    [
                        'uid'=>$user->uuid,
                        'duration'=>@$request->duration?:100000,
                        'ip'=>$ip->ip,
                        'type'=>'all',
                        'user_type'=>0,
                        'staff_id'=>Auth::id ()
                    ]
                );
            }
        }
        if (in_array ('device',$request->type)){
            Ban::query ()->create (
                [
                    'uid'=>$user->uuid,
                    'duration'=>@$request->duration?:100000,
                    'device_number'=>$user->device_token,
                    'type'=>'all',
                    'user_type'=>0,
                    'staff_id'=>Auth::id ()
                ]
            );
        }
        if (in_array ('normal',$request->type)){
            Ban::query ()->create (
                [
                    'uid'=>$user->uuid,
                    'duration'=>@$request->duration?:100000,
                    'type'=>'all',
                    'user_type'=>0,
                    'staff_id'=>Auth::id ()
                ]
            );
        }

        if($room){
            $d = [
                "messageContent"=>[
                    "message"=>"banDevice",
                    "userId"=>$user->id
                ]
            ];
            $json = json_encode ($d);

            Common::sendToStream ('SendCustomCommand',$room->id,$user->id,$json);
        }
        CustomNotification::banUser($user, $request->duration);

        return $this->response()->success('success')->refresh();

    }

    public function form()
    {
        $this->text('uid', __('user id'));
        $this->integer('duration', __('duration(hours) : empty if forever'));
        $this->checkbox('type', __('type'))->options ([
            'normal' => __('normal'),
          //  'ip' => __('ip'),
            'device' => __('device'),
        ]);
    }

    public function html()
    {
        return <<<HTML

    <li><a href="javascript:void(0);" class="ban_user_action "><i class="fa fa-dollar text-red"></i> حظر</a></li>

HTML;
    }
}
