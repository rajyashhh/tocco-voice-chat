<?php


namespace App\Traits\HelperTraits;


use App\Helpers\Common;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait MoneyTrait
{


//Obtain information about the time of withdrawal
    public static function get_tixian_info ()
    {
        $info               = DB ::table ( 'tixians' )
            -> where ( 'status' , 'normal' )
            -> orderBy ( 'id' , 'desc' )
            -> first ();
        $info['start_time'] = strtotime ( date ( 'H:i' , $info['start_time'] ) );//start timestamp
        $info['end_time']   = strtotime ( date ( 'H:i' , $info['end_time'] ) );//end timestamp
        $info['week_ids']   = $week_names = array_filter ( explode ( ',' , $info['week_ids'] ) );
        return $info;
    }


    /**
     * withdraw
     * @param int       money       Withdrawal Amount
     * @param int       type        1 meter coin withdrawal 2 whale coin value withdrawal
     */
    public function tixian ($user_id)
    {
        //Judging whether to join the guild, joining can not be withdrawn
        $union_info = DB ::table ( 'user_union' )
            -> where ( ['check_status' => '1' , 'users_id' => $user_id] )
            -> first ();
        if ( $union_info ) {
            return 'Already joined the guild, temporarily unable to withdraw cash';
        }
        //Judging whether it is possible to withdraw cash
        $tixian_info = DB ::table ( 'tixians' )
            -> where ( 'status' , 'normal' )
            -> orderBy ( 'id' , 'desc' )
            -> first ();
        if ( $tixian_info ) {
            $tixian_info['week_ids'] = $week_names = array_filter ( explode ( ',' , $tixian_info['week_ids'] ) );
            $weekarray               = date ( "w" );
            if ( $weekarray == 0 ) {
                $weekarray = 7;
            }
            $start_time   = strtotime ( date ( 'H:i' , $tixian_info['start_time'] ) );//start timestamp
            $current_time = strtotime ( date ( 'H:i' , time () ) );//当前时间戳
            $end_time     = strtotime ( date ( 'H:i' , $tixian_info['end_time'] ) );//start timestamp
            if ( $start_time > $current_time || $current_time > $end_time ) {
                return 'Please withdraw cash at the specified time';
            }
            if ( ! in_array ( $weekarray , $tixian_info['week_ids'] ) ) {
                return 'Please withdraw cash on the specified working day';
            }
        }


        $money = request () -> get ( 'money' );
        $type  = request () -> get ( 'type' );
        if ( $money <= 0 || ! in_array ( $type , [1 , 2] ) ) return 'Missing parameters';
        $user       = DB ::table ( 'users' ) -> where ( 'id' , $user_id ) -> select ( 'coins' , 'room_coins' , 'flowers_value' ) -> first ();
        $min_tx_num = self::getConfig ( 'min_tx_num' );
        if ( $money < $min_tx_num ) return 'Withdrawal amount cannot be less than' . $min_tx_num;

        if ( $type == 1 ) {
            $sum = bcadd ( $user -> coins , $user -> room_coins , 2 );
            if ( $money > $sum ) return 'Insufficient balance';
        }
        elseif ( $type == 2 ) {
            if ( $money > $user -> flowers_value ) return 'Insufficient balance';
        }

        DB ::beginTransaction ();
        try {
            if ( $type == 1 ) {
                if ( $user['coins'] >= $money ) {
                    self::userStoreDec ( $user_id , $money , 23 , 'coins' );
                }
                else {
                    self::userStoreDec ( $user_id , $user->Room_coins , 34 , 'Room_coins' );
                    $num = bcsub ( $money , $user->Room_coins , 2 );
                    self::userStoreDec ( $user_id , $num , 23 , 'coins' );
                }
            }
            elseif ( $type == 2 ) {
                self::userStoreDec ( $user_id , $money , 52 , 'flowers_value' );
            }

            $arr['order_no'] = self::getOrderNo ();
            $arr['user_id']  = $user_id;
            $arr['money']    = $money;
            $arr['type']     = $type;
            $arr['addtime']  = time ();
            $res             = DB ::table ( 'tixian' ) -> insert ( $arr );
            //commit transaction
            DB ::commit ();
        } catch (\Exception $e) {
            //rollback transaction
            DB ::rollback ();
            return 'Withdrawal failed';
        }
        return 'The withdrawal application has been submitted and is under review';
    }



    //reduce the value
    public static function userStoreDec($user_id, $get_nums, $get_type, $jb_type) {
        $get_nums=abs($get_nums);
        if($get_nums == 0) return false;
        $res = User::query()->findOrFail ($user_id)->decrement($jb_type, $get_nums);
        if (!$res) return false;
        $now_nums = DB::table('users')->where(['id' => $user_id])->value($jb_type);
        self::addTranmoney($user_id, $get_nums, $get_type, $now_nums,'-');
        return $res;
    }


    //create record
    public static function addTranmoney($user_id, $get_nums, $get_type, $now_nums,$symbol,$union_id = 0) {
        $info['union_id']  = $union_id;
        $info['user_id']  = $user_id;
        $info['get_nums'] = $get_nums;
        $info['get_type'] = $get_type;
        $info['now_nums'] = $now_nums;
        $info['symbol']    = $symbol;
        $info['types']    = self::getTypesByGettype($get_type);
        $res = Db::table('store_logs')->insertGetId($info);
        return $res;
    }


    // Classification of user resources
    public static function getTypesByGettype($gettype){
        if (in_array($gettype, [11,12,13,14,15,16,17,18,19])){
            return 1;
        }else if(in_array($gettype, [21,22,23,24,25,26])){
            return 2;
        }else if(in_array($gettype, [31,32,33,34,35,36,37])){
            return 3;
        }else if(in_array($gettype, [41,43,44,45,46])){
            return 4;
        }else if(in_array($gettype, [51,52,53,64])){
            return 5;
        }else if(in_array($gettype, [61,62,63])){
            return 6;
        }
        return 0;
    }



    public static function getOrderNo($prefix = 'MN') {
        $order_no = $prefix . date("YmdHis") . rand(100000, 999999);
        return $order_no;
    }



    public static function userPackStoreInc($user_id,$type,$target_id,$get_nums,$use_type = '5',$get_type='5') {
        $get_nums=abs($get_nums);
        if($get_nums == 0) return false;

        $where['user_id']=$user_id;
        $where['type']=$type;
        $where['target_id']=$target_id;
        $num=DB::table('pack')->where($where)->first();
        $wares=DB::table('wares')->find($target_id);
        if(!$num){
            //$params = $_POST['row'];
            $info['user_id']=$user_id;
            $info['get_type']=$get_type;
            $info['type']=$type;
            $info['target_id']=$target_id;
            $info['num']=$get_nums;
            $info['expire']= $wares->expire ? time()+($wares->expire * 86400 * $get_nums) : 0;
            //$info['expire']= $params['expire'] ? strtotime($params['expire']) : 0;
            $info['addtime']=time();
            $res=DB::table('pack')->insertGetId($info);
            $now_nums=$get_nums;
        }else{
            $res=DB::table('pack')->where($where)->increment('num',$get_nums);
            $now_nums=DB::table('pack')->where($where)->value('num');
            $pack_expire=DB::table('pack')->where($where)->value('expire');
            $expire=$wares['expire'] ? ($pack_expire + ($wares['expire'] * 86400 * $get_nums)) : 0;
            DB::table('pack')->where($where)->update(array('expire'=>$expire));
        }


        if(in_array($type, [1,2,3])){//gems, gifts, coupons
            self::addPackLog($user_id,$type,$target_id,$get_nums,$use_type,$now_nums);
        }
        return $res;
    }
//reduce the value
    public static function userPackStoreDec($user_id,$type,$target_id,$get_nums,$use_type = '1') {
        $get_nums=abs($get_nums);
        if($get_nums == 0) return false;
        $where['user_id']=$user_id;
        $where['type']=$type;
        $where['target_id']=$target_id;
        $num=DB::table('packs')->where($where)->value('num');
        if(!$num || $num < $get_nums)   return false;
        if($num == $get_nums){
            $res = DB::table('pack')->where($where)->delete();
            $now_nums=0;
        }else{
            $res = DB::table('packs')->where($where)->decrement('num',$get_nums);
            $now_nums=DB::table('packs')->where($where)->value('num');
        }
        self::addPackLog($user_id,$type,$target_id,$get_nums, $use_type, $now_nums);
        return $res;
    }
//create record
    public static function addPackLog($user_id,$type,$target_id,$get_nums, $use_type, $now_nums) {
        $info['user_id'] = $user_id;
        $info['use_type'] = $use_type;
        $info['type'] = $type;
        $info['target_id'] = $target_id;
        $info['get_nums'] = $get_nums;
        $info['now_nums'] = $now_nums;
        $info['addtime'] = time();
        $res = Db::table('pack_logs')->insertGetId($info);
        return $res;
    }

//increase value
    public static function userStoreInc($user_id, $get_nums, $get_type, $jb_type) {
        $get_nums=abs($get_nums);
        if($get_nums == 0) return false;
        if ($get_type == 99) {
            $now_nums = 0;
            $res = 1;
        } else {
            $res = Db::table('users')->where(['id' => $user_id])->increment($jb_type, $get_nums);
            $now_nums = Db::table('users')->where(['id' => $user_id])->value($jb_type);
        }
        if (!$res) return false;
        self::addTranmoney($user_id, $get_nums, $get_type, $now_nums,'');
        return $res;
    }
//Increase guild value
    public static function userUnionStoreInc($user_id, $get_nums, $get_type, $jb_type) {
        $get_nums=abs($get_nums);
        if($get_nums == 0) return false;
        if ($get_type == 99) {
            $now_nums = 0;
            $res = 1;
        } else {
            //Increase guild income
            Db::table('user_unions')->where(['users_id' => $user_id])->increment('total_price', $get_nums);
            $res = Db::table('user_unions')->where(['users_id' => $user_id])->increment($jb_type, $get_nums);
            Db::table('user_unions')->where(['users_id' => $user_id])->increment('unsettled_price', $get_nums);
            $now_nums = Db::table('user_unions')->where(['users_id' => $user_id])->selectRaw($jb_type.',union_id')->first();
        }
        if (!$res) return false;
        self::addTranmoney($user_id, $get_nums, $get_type, $now_nums[$jb_type],'',$now_nums['union_id']);
        return $res;
    }


    /**
     * Get the commission ratio or patriarch id
     * @param int                   user_id             user id
     * @param int                   type                Return data 1: return the handling fee ratio, 2: return the patriarch id
     */
    public  static function getFeeRatio($user_id,$type = 1){
        $family_id=Db::table('family_user')->where(['status'=>1,'user_id'=>$user_id])->value('family_id');
        $f_user_id=Db::table('family_user')->where(['status'=>1,'user_type'=>2,'family_id'=>$family_id])->value('user_id');
        if($type == 1){
            //Not joined the family 10%, joined the family 20%
            $no_family_ratio = static::getConfig('no_family_ratio');//not joined
            $no_family_ratio = $no_family_ratio ? $no_family_ratio/100 : 0.1;
            $is_family_ratio = static::getConfig('is_family_ratio');//joined
            $is_family_ratio = $is_family_ratio ? $is_family_ratio/100 : 0.2;
            return $f_user_id ? $is_family_ratio : $no_family_ratio;
        }elseif($type == 2){
            return $f_user_id ? : null ;
        }
    }

    public static function getUnionFeeRatio($user_id,$type = 1){
        $union_id = Db::table('user_union')->where(['check_status'=>1,'users_id'=>$user_id])->value('union_id');
        $info=Db::table('union')->where(['status'=>1,'check_status'=>1,'id'=>$union_id])->selectRaw('id,users_id,share')->first();
        if($type == 1){
            //If you set the guild share ratio, take the user ratio value, otherwise take the default value
            return $info['share'] ? $info['share'] : static::getConfig('union_share');
        }elseif($type == 2){
            return $info['id'] ? : null ;
        }
    }





}
