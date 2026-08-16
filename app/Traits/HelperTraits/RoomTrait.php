<?php


namespace App\Traits\HelperTraits;


use App\Helpers\Common;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\LiveTime;
use App\Models\Mic;
use App\Models\Pk;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait RoomTrait
{

    public static function get_room_users($owner_id,$user_id){
        $room = Room::query()->where(['uid'=>$owner_id])->select('id','microphone')->first();
        if(!$room)   return __('room does not exist');
//        if($owner_id == $user_id)    return __('No operation authority for homeowners');

        $mic_arr=$room->microphone ? explode(',', $room->microphone) : [];
        foreach ($mic_arr as $k => &$v) {
            if($v == 0 || $v == -1 || $v == $owner_id)   unset($mic_arr[$k]);
        }

        // Use repository for visitor operations
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        $vis_arr = $visitorRepo->getVisitorIds($room->id)->toArray();

        // Security check: validate user is in room
        if($user_id && !$visitorRepo->isVisitor($room->id, $user_id))    return __('User is not in this room');
        $sea_user=array();
        $mic_user=User::query ()->whereIn('id',$mic_arr)->with ('profile')->get ();
        foreach ($mic_user as $k => &$v){
            $v->is_mic=1;
            if($user_id == $v->id)  $sea_user[]=$v;
        }
        unset($v);


        //Arranging mic or ordering personnel
        $pm_arr=$paimai=$shiyin=[];
        $paimai_data=Mic::where('roomowner_id',$owner_id)->select('type','created_at','user_id','roomowner_id')->get ();
        $i=$j=0;
        foreach ($paimai_data as $k => &$v2) {
            $v2->id = $v2->user->id;
            $v2->is_mic=0;
            $v2->name = @$v2->user->name;
            $v2->avatar = @$v2->user->profile->avatar;

            if($v2->type==1){
                $i++;
                $v2->sort=$i;
                $paimai[]=$v2;
            }elseif($v2->type==2){
                $j++;
                $v2->sort=$j;
                $shiyin[]=$v2;
            }

            $pm_arr[]=$v2->user_id;
            if($user_id == $v2->user_id) $sea_user[]=$v2;
            unset($v2->user);
            unset($v2->user_id);
            unset($v2->roomowner_id);
        }
        unset($v2);


        //

        //people in the room
        $vis_arr=array_diff($vis_arr,$mic_arr);
        $vis_arr=array_diff($vis_arr,$pm_arr);
        $room_user=User::query ()->whereIn('id',$vis_arr)->get ();
        foreach ($room_user as $k1 => &$v1){
            $v1->is_mic=0;
            if($user_id == $v1->id) $sea_user[]=$v1;
        }

        unset($v1);



        $data['mic_users']= UserResource::collection ($mic_user) ;
//        $data['auction']= $paimai;
//        $data['audio']=  $shiyin ;
        $data['room_users']= UserResource::collection ($room_user);
//        $data['sea_users']= UserResource::collection ($sea_user);


        return $data;
    }

    public static function get_room_users_2($owner_id,$user_id){
        $room = Room::query()->where('uid', $owner_id)->select('id', 'uid')->with(['microphones.user.profile'])->first();

        if(!$room)   return __('room does not exist');

//        $mic_arr=$room->microphone ? explode(',', $room->microphone) : [];
//        foreach ($mic_arr as $k => &$v) {
//            if($v == 0 || $v == -1 || $v == $owner_id)   unset($mic_arr[$k]);
//        }

        $mic_arr = $room->microphones->whereNotIn('user_id', [0, -1, $owner_id])->pluck('user_id')->filter()->values()->all();

        // Use repository for visitor operations
        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        $vis_arr = $visitorRepo->getVisitorIds($room->id)->toArray();

        // Security check: validate user is in room
        if($user_id && !$visitorRepo->isVisitor($room->id, $user_id))    return __('User is not in this room');
        $sea_user=array();

//        $mic_user=User::query ()->whereIn('id',$mic_arr)->with ('profile')->get ();
//        foreach ($mic_user as $k => &$v){
//            $v->is_mic=1;
//            if($user_id == $v->id)  $sea_user[]=$v;
//        }
//        unset($v);

        $mic_user = $room->microphones
            ->whereNotIn('user_id', [0, -1, $owner_id])
            ->pluck('user')
            ->filter()
            ->map(function ($user) use ($user_id) {
                $user->is_mic = 1;
                if ($user->id == $user_id) {
                    $user->is_self = true;
                }
                return $user;
            })
            ->values();

        $pm_arr=$paimai=$shiyin=[];
        $paimai_data=Mic::where('roomowner_id',$owner_id)->select('type','created_at','user_id','roomowner_id')->get ();
        $i=$j=0;
        foreach ($paimai_data as $k => &$v2) {
            $v2->id = $v2->user->id;
            $v2->is_mic=0;
            $v2->name = @$v2->user->name;
            $v2->avatar = @$v2->user->profile->avatar;

            if($v2->type==1){
                $i++;
                $v2->sort=$i;
                $paimai[]=$v2;
            }elseif($v2->type==2){
                $j++;
                $v2->sort=$j;
                $shiyin[]=$v2;
            }

            $pm_arr[]=$v2->user_id;
            if($user_id == $v2->user_id) $sea_user[]=$v2;
            unset($v2->user);
            unset($v2->user_id);
            unset($v2->roomowner_id);
        }
        unset($v2);

        $vis_arr=array_diff($vis_arr,$mic_arr);
        $vis_arr=array_diff($vis_arr,$pm_arr);
        $room_user=User::query ()->whereIn('id',$vis_arr)->get ();
        foreach ($room_user as $k1 => &$v1){
            $v1->is_mic=0;
            if($user_id == $v1->id) $sea_user[]=$v1;
        }

        unset($v1);

        $data['mic_users']= UserResource::collection ($mic_user) ;
        $data['room_users']= UserResource::collection ($room_user);

        return $data;
    }

    //Get blacklist list
    public static function getUserBlackList($user_id = null) {
        if (!$user_id) return [];
        $ids = DB::table('black_lists')->where('user_id', $user_id)->where('status', 1)->pluck('from_uid')->toArray ();
        return $ids;
    }

    public static function getUserBlackListInRoom($userId , $currentUserId) {
        if ($userId == $currentUserId) return false;
        return  DB::table('black_lists')
            ->where(fn ($query) => $query->where('user_id', $currentUserId)->where('from_uid', $userId))
            ->orWhere(fn ($query) => $query->where('user_id', $userId)->where('from_uid', $currentUserId))
            ->exists();
    }

    public static function userNowRoom($user_id = null)
    {
        if (!$user_id) {
            return false;
        }
        $user = User::query ()->find ($user_id);
        if (!$user){
            return false;
        }
        $is_afk = DB::table('rooms')->where('uid', $user_id)->value('is_afk');
        if ($is_afk) {
            return $user_id;
        }
        $uid = DB::table('rooms')->where('uid',  $user->now_room_uid)->value('uid');
        return $uid ?: 0;
    }

    public static function userNowRooms($user_id = null)
    {
        if (!$user_id) {
            return false;
        }
        $is_afk = Room::query ()->where('uid', $user_id)->value('is_afk');
        if ($is_afk) {
            return $user_id;
        }
        $uid = Room::query ()->where('uid',$user_id)->value('uid');
        return $uid ?: 0;
    }

    public static function getRoomInfo($user_id = null){
        $room_id = self::userNowRooms ($user_id);
        if ($room_id) {
            $roomInfo = Room::query ()->select(['uid', 'room_name', 'hot','room_cover'])->where('uid', $room_id)->first ();
            $roomInfo['hot'] = self::room_hot($roomInfo['hot']);
            $roomInfo['room_name'] = urldecode($roomInfo['room_name']);
        } else {
            $roomInfo =(object)[];
        }
        return $roomInfo;
    }

    public static function calcTime($uid)
    {
        $user  = User::find($uid);
        $timer =
            LiveTime::query()->where('uid', $uid)->whereDate('created_at', today())->where('end_time', null)->orderByDesc('id')->first();
        if ($timer) {
            $hours           = round((time() - $timer->start_time) / (60 * 60), 2);
            $timer->end_time = time();
            $timer->hours    = $hours;
            $timer->save();
            //$user_day = UserDay::where('user_id', $uid)->whereDate('created_at', today())->first();
            $user_hours =
                LiveTime::query()->where('uid', $user->id)->whereYear('created_at', '=', Carbon::now()->year)->whereMonth('created_at', '=', Carbon::now()->month)->whereDay('created_at', '=', Carbon::now()->day)->sum('hours');


            $hours = (int)$user_hours;

            if ($hours >= 1 && $user->today_days == 0) {
                DB::statement("
                UPDATE users
                SET today_days = 1
                WHERE id = :id
            ", ['id' => $user->id]);
            }
        }
    }


    //exit room - perform action
    public static function quit_hand($uid,$user_id){
        $room = Room::query ()->where('uid',$uid)->first ();
        if (!$room) {
            return '';
        }

        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        $room_visitor = $visitorRepo->getVisitorIds($room->id)->toArray();

        //homeowner exits room
        if($uid == $user_id){
            if ($room){
                $room->update (['is_afk'=>0]);
            }
            Room::query ()->where('uid',$uid)->update(['is_afk'=>0]);
        }

        // Security check: validate user is in room
        if( $uid != $user_id && !$visitorRepo->isVisitor($room->id, $user_id)){
            return implode(',', $room_visitor);
        }

        foreach ($room_visitor as $k => &$v) {
            if($user_id == $v){
                unset($room_visitor[$k]);
            }
        }
        $new_visitor=trim(implode(',', $room_visitor),',');
//        if ($room){
//            $room->update (['room_visitor'=>$new_visitor]);
//        }
//        Room::query ()->where('uid',$uid)->update(['room_visitor'=>$new_visitor]);
        //mic
        self::go_microphone_hand($uid,$user_id);
        //Remove mic
        self::delMicHand($user_id);
//        self::updrycheck($user_id);
        return $new_visitor;
    }

    public static function quit_hand_2($uid,$user_id){
        $room = Room::query ()->where('uid',$uid)->first ();
        if (!$room) {
            return '';
        }

        $visitorRepo = app(\App\Repositories\RoomVisitorRepository::class);
        $room_visitor = $visitorRepo->getVisitorIds($room->id)->toArray();

        if($uid == $user_id){
            if ($room){
                $room->update (['is_afk'=>0]);
            }
            Room::query ()->where('uid',$uid)->update(['is_afk'=>0]);
        }

        // Security check: validate user is in room
        if( $uid != $user_id && !$visitorRepo->isVisitor($room->id, $user_id)){
            return implode(',', $room_visitor);
        }

        foreach ($room_visitor as $k => &$v) {
            if($user_id == $v){
                unset($room_visitor[$k]);
            }
        }
        $new_visitor=trim(implode(',', $room_visitor),',');
        self::go_microphone_hand_2($uid,$user_id);
        self::delMicHand($user_id);
        return $new_visitor;
    }
    //Down the wheat - execute the operation
    public static function go_microphone_hand($uid,$user_id){
        $room      = Room::withoutAppends()->where('uid', $uid)->select(['id', 'uid', 'microphone'])->first();
        $microphone = $room->microphone;
        $mainMicrophone = $room->main_microphone;
        $baseMic = $room->all_microphone;
        // $baseMic = $room->getOriginal('microphone');


        $microphone = explode(',', $microphone);
        $mainMicrophone = explode(',', $mainMicrophone);
        $baseMic = explode(',', $baseMic);
        if(!$microphone || !in_array($user_id, $microphone)){
            return 0;
        }
        $position = 0;
        for ($i=0; $i < count($microphone); $i++) {
            if($microphone[$i] == $user_id){
                $position = $i;
                break;
            }
        }
        if ($microphone[$position] > 0){
            $baseMic[$position] = $mainMicrophone[$position];
        }


        $result = DB::table('rooms')->where('uid',$uid)->update(['microphone'=>implode(',', $baseMic)]);
        $room = Room::query ()->where ('uid',$uid)->first ();
        $pk = Pk::query ()->where ('room_id',$room->id)->where ('status',1)->first ();
        if ($pk){
            $pk->mics = $microphone;
            $pk->save ();
        }
        //clear timer
        Db::table('time_logs')->where(['uid'=>$uid,'user_id'=>$user_id])->delete();
        /*$ms = [
            "messageContent"=>[
                "message"=>"leaveMic",
                "userId"=>$user_id,
                "position"=>$position
            ]
        ];
        $json = json_encode ($ms);
        Common::sendToStream ('SendCustomCommand',$room->id,$user_id,$json);*/
        return $result;
    }

    public static function go_microphone_hand_2($uid,$user_id){
        $room = Room::withoutAppends()->where('type', 'audio')->where('uid', $uid)->select(['id', 'uid', 'microphone'])->first();

        if (!$room) {
            // Log::warning("Room not found for UID: {$uid}");
            return 0;
        }
    
        $micSeat = $room->microphones()
            ->where('user_id', $user_id)
            ->first();

            $micSeat2 = $room->microphones();

        if (!$micSeat) {
            // Log::warning("User ID {$user_id} is not on microphone in Room UID: {$uid}");
            return 0;
        }
    
        $micSeat->delete();
    
        $micString = $room->microphones()
            ->orderBy('position')
            ->get()
            ->map(function ($mic) {
                $userId = $mic->user_id ?? 0;
                $status = $mic->status ?? 0;
    
                return $userId > 0 ? "{$userId}#{$status}" : (string)$status;
            })
            ->implode(',');
    
    
        $pk = Pk::query()->where('room_id', $room->id)->where('status', 1)->first();
        if ($pk) {
            $pk->mics = $micString;
            $pk->save();
        }
    
        DB::table('time_logs')->where(['uid' => $uid, 'user_id' => $user_id])->delete();
    
        return 1;
    }

    //Remove mic discharge operation
    public static function delMicHand($user_id){
        $res=DB::table('mics')->where(['user_id'=>$user_id])->delete();
        return $res;
    }




    public static function roomDataFormat($data = array())
    {
        if (!$data) {
            return [];
        }
        foreach ($data as $k => &$v) {
            $v['room_name'] = urldecode($v['room_name']);
            if (isset($v['hot'])) {
                $v['hot'] = self::room_hot($v['hot']);
            }
            if (isset($v['microphone'])) {
                $mic_arr = explode(',', $v['microphone']);
                $zc = array_pop($mic_arr);
                $v['host'] = $zc > 1000 ? self::getUserField($zc, 'nickname') : '';
            }
        }
        return $data;
    }


    //Get user information
    public static function getUserField($user_id=null,$field=null){
        if(!$user_id || !$field)    return '';
        $user = User::query ()->find ($user_id);
        if(in_array ($field,['avatar','gender','birthday','province','city','country'])){
            return @$user->profile->{$field};
        }
        $value=@$user->{$field};
        return $value ? : '';
    }



    //Get nickname color according to vip level
    public static function getNickColorByVip($level=0){
        $color = '#ffffff';
        if($level < 3){
            $color='#ffffff';
        }elseif($level>=3 && $level<7){
            $color='#93ffa5';
        }elseif($level>=7 && $level<11){
            $color='#8ce1fe';
        }elseif($level>=11 && $level<15){
            $color='#ffc6e1';
        }elseif($level>=15 && $level<18){
            $color='#e09dff';
        }elseif($level>=18 && $level<=20){
            $color='#fff585';
        }else{
            $color = '#000000';
        }
        return $color;
    }




    //Increase the accumulative value after opening the room mode. Added in the fourth phase
    public static function add_play_num($uid,$user_id,$price){
        $where['uid']=$uid;
        $where['user_id']=$user_id;
        $data=DB::table('play_num_logs')->where($where)->first();
        if(!$data){
            $info['uid']=$uid;
            $info['user_id']=$user_id;
            $info['price']=$price;
            DB::table('play_num_logs')->insertGetId($info);
        }else{
            DB::table('play_num_logs')->where($where)->increment('price',$price);
        }

    }







    public static function update_user_total($user_id = null, $type = null, $coins = null)
    {
        if (!$user_id || !$type || !$coins) {
            return false;
        }
        $data = DB::table('user_totals')->where(['user_id' => $user_id])->first();
        if ($type == 1) {
            DB::table('user_totals')->where(['user_id' => $user_id])->increment('room', $coins);
        } elseif ($type == 2) {
            DB::table('user_totals')->where(['user_id' => $user_id])->increment('send', $coins);
            $vip_level = self::getLevel_two($user_id, 3);
            $res = DB::table('user_totals')->where(['user_id' => $user_id])->update(['vip_level' => $vip_level]);
            if ($res) {
                self::add_user_official($user_id, 1, $vip_level);
            }
        } elseif ($type == 3) {
            DB::table('user_totals')->where(['user_id' => $user_id])->increment('gain', $coins);
        } elseif ($type == 4) {
            $cp_level = self::getUserMaxCpLevel($user_id, 'level');
            $res = DB::table('user_total')->where(['user_id' => $user_id])->update(['cp_level' => $cp_level]);
            if ($res) {
                self::add_user_official($user_id, 8, $cp_level);
            }
        } else {
            return false;
        }
    }

    public static function getLevel_two($user_id = null, $type = null, $is_img = null)
    {
        if (!$user_id || !$type) {
            return 0;
        }
        $gold_num = DB::table('user_totals')->where('user_id', $user_id)->value('send');
        $star_num = DB::table('user_totals')->where('user_id', $user_id)->value('gain');
        if ($type == 1) {
            $coins = $star_num;
        } elseif ($type == 2 || $type == 3) {
            $coins = $gold_num;
        } else {
            return 0;
        }
        if (!$coins && $is_img == 'img') {
            return '';
        }
        if (!$coins) {
            return 0;
        }
        $level = DB::table('vips')->where(['type' => $type])->where('mizuan', '<=', $coins)->orderBy('id', 'desc')->limit(1)->value('level');

        if ($is_img == 'img') {
            if ($level) {

                $img = DB::table('vips')->where(['level' => $level, 'type' => $type])->value('img');
                return $img;
            } else {
                return '';
            }
        } else {
            return $level;
        }
    }
    public static function getUserMaxCpLevel($user_id = null, $field = null)
    {
        if (!$user_id || !$field) {
            return 0;
        }
        $where['user_id|fromUid'] = $user_id;
        $where['status'] = 1;
        $cp = DB::table('cps')->where($where)->orderByRaw('exp desc')->limit(1)->first();
        if (!$cp) {
            return 0;
        }
        $exp = $cp->exp;
        $where_vip['type'] = 5;
        $where_vip['exp'] = ['elt', $exp];
        $level = DB::table('vips')->where($where_vip)->orderByRaw('id desc')->limit(1)->value('level');
        if ($field == 'level') {
            return $level;
        } else {
            return $cp[$field] ?: 0;
        }
    }

    public static function add_user_official($user_id, $get_type, $level)
    {
        if ($get_type == 1) {
            $where['type'] = 3;
            $class = "VIP";
        } elseif ($get_type == 8) {
            $where['type'] = 5;
            $class = "Guardian CP";
        } else {
            return false;
        }
        $where['level'] = $level;
        $where['enable'] = 1;
        $auth = DB::table('vip_auth')->where($where)->value('name');
        if (!$auth) {
            return false;
        }
        $content = "Congratulations," . $class . 'level reached' . $level . 'Level up, successfully unlocked' . $auth . 'privilege~';
        self::addOfficialMessage('', $user_id, $content);
    }

   protected static function addOfficialMessage($title  , $user_id, $content =null) {

        $title = $title ?: 'system notification';
        $info['title'] = $title;
        $info['user_id'] = $user_id;
        $info['content'] = $content;
        $info['created_at'] = date('Y-m-d H:i:s', time());
        $res = DB::table('official_messages')->insertGetId($info);
        return $res;
    }

    public static function fin_task($user_id,$task_id){
        $task=Db::table('tasks')->where(['id'=>$task_id,'enable'=>1])->first();
        if(!$task)  return 0;
        $user_task=Db::table('user_tasks')->where(['user_id'=>$user_id])->first();
        if($task->type == 1 && !substr_count($user_task->not_fin_1,$task_id))   return 0;
        $field='fin_'.$task->type;
        $str=$user_task->{$field};
        $num=substr_count($str,$task_id);
        if($num == $task->num)    return 0;
        $str_arr=explode(',', $str);
        $str_arr[]=$task_id;
        $info[$field]=trim(implode(',', $str_arr),',');
        Db::table('user_tasks')->where(['user_id'=>$user_id])->update($info);
        return 1;
    }


    public static function can_kick($user_id){
        $vip_level      = self ::getLevel ( $user_id , 3 );
        $vip_auth = DB ::table ( 'vip_auth' ) -> where ( ['type' => 3 , 'enable' => 1] )->whereIn ('name',['ممنوع الطرد','not kicked']) ;
        $vip_auth = $vip_auth-> get ();
        $can_kick = true;
        foreach ($vip_auth as $k => &$v) {
            if ($vip_level >= $v->level){
                $can_kick = false;
            }
        }
        return $can_kick;
    }

    public static function increaseRoomSession($owner_id,$num){
        Room::query ()->where ('uid',$owner_id)->increment ('session',$num);
    }
}
