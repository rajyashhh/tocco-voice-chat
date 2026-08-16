<?php

namespace App\Admin\Actions;

use App\Helpers\PackHelper;
use Carbon\Carbon;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Encore\Admin\Actions\RowAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Classes\Enums\SubTypeMessagesType;
use Modules\Public\Http\Services\UserCounterServices;
use Modules\Public\Http\Services\UpgradeLevelServices;
use Modules\Public\Http\Services\UpgradeServices;
use Modules\Vip\Helpers\VipCommon;

class DedicateAction extends RowAction
{
    public $name = 'Dedicate';

    public function handle(Model $model, Request $request)
    {
        $user = User::query()->searchByUuid($request->user_uuid)->first();
        if (!$user) {
            return $this->response()->error(__('dashboard.userNotFound'))->refresh();
        }
        if ($model instanceof Ware) {
            $ware = $model;
            if ($ware->type == 25){
               $special_id_check= Pack::query()->where('target_id', $ware->id)->first();
               if ($special_id_check){
                   return $this->response()->error(__('dashboard.taken'))->refresh();
               }
            }

            $pack = Pack::query()->where('user_id', $user->id)->where('target_id', $ware->id)->first();
            if ($pack) {
                if ($pack->expire == 0) return $this->response()->error(__('dashboard.chickTaken'))->refresh();
                if ($pack->expire > now()->timestamp) {
                    if ($ware->expire != 0) {
                        DB::beginTransaction();
                        try {

                            $pack->expire += (($request->days ??$ware->expire) * 86400);
                            $pack->save();
                            if ($ware->type == 25) {
                                $user->special_id = $ware->value;
                                $user->save();
                            }
                            DB::commit();
                            //  Common::sendOfficialMessage ($user->id,__('congratulations'),StringFacade::gotGift($ware->name), 1, SubTypeMessagesType::GOT_GIFT);
                            // $tokens_notfacion[] = DB::table('users')->where('id', $user->id)->value('notification_id');
                            // $title = 'Tik Chat';
                            // $body = __('لقد حصلت على اهداء') . $request->user()->name;
                            //  Common::send_firebase_notification($tokens_notfacion,$title,$body);
                            (new UserCounterServices)->eventUser($user,'mybag',1);
                            return $this->response()->success(__('dashboard.successful'));
                        } catch (\Exception $exception) {
                            DB::rollBack();
                            return $this->response()->error('خطا غير متوقع');
                        }
                    } else {
                        return $this->response()->error(__('dashboard.chickTaken'));
                    }
                } else {
                    $pack->delete();
                }
            }
            DB::beginTransaction();
            try {
                $arr['user_id'] = $user->id;
                $arr['type'] = $ware->type;
                $arr['get_type'] = $ware->get_type;
                $arr['target_id'] = $ware->id;
                $arr['num'] = 1; //$qty;
                $arr['expire'] = $request->days ? time() + (($request->days ?? $ware->expire) * 86400) : 0;
                $arr['is_read'] = 1;
                $arr['receive_type'] ='dash-dedicate';

                $enableVipAuto = Common::getConf('enable_vip_auto') ?? "false";
                $arr['is_used'] = $enableVipAuto === "true" ? 1 : 0;

                Pack::query()->create($arr);
                if ($ware->type == 25) {
                    $user->special_id = $ware->value;
                    $user->save();
                }


                DB::commit();
                (new UserCounterServices)->eventUser($user,'mybag',1);
                CustomNotification::wareVip($user, $request->days, $ware->name, $ware->show_img);
                return $this->response()->success(__('dashboard.successful'));
            } catch (\Exception $exception) {
                DB::rollBack();
                return $this->response()->error('خطا غير متوقع');
            }
        }
        elseif ($model instanceof OVip) {
            $vip = $model;
            // admin only put to user vip greater than 30 days
            if (!Admin::user()->can('*') && $request->days > 30){
                return $this->response()->error(__('dashboard.addAchivement'))->refresh();
            }
            DB::beginTransaction();


            $enableVipAuto = Common::getConf('enable_vip_auto') ?? "false";
            $is_used = $enableVipAuto === "true" ? 0 : 0;

            try {
                $uniqueAttributes = [
                    'sender_id' => 0,
                    'user_id'   => $user->id,
                    'vip_id'    => $vip->id,
                    'level'     => $vip->level,
                ];
                $userVip = UserVip::query()->where($uniqueAttributes)->first();
                if (!$userVip) {

                    VipCommon::createUserVip($vip ,$user ,$request->days , auth()->id() ,'',1,0,0,'dash-dedicate');

                } else {
                    $userVip->qty++;
                    if($userVip->expire > now()->timestamp){
                        $userVip->expire += ($request->days * 86400);
                        $userVip->is_used += $is_used;
                    }else{
                        $userVip->expire = now()->timestamp + ($request->days * 86400);
                        $userVip->is_used += $is_used;

                    }
                    $userVip->save();
                }
                // VipCommon::handelVip($vip, $user, expire: $request->days ?? 1, userVip: $userVip);

                DB::commit();
                CustomNotification::vips($user, $request->days, $vip->img);
                return $this->response()->success(__('dashboard.successful'));
            } catch (\Exception $exception) {

                echo($exception->getMessage());
                DB::rollBack();
                return $this->response()->error('خطا.')->refresh();
            }
        } else {
            return $this->response()->error('خطا.')->refresh();
        }
    }

    public function form()
    {
        $this->integer('days', 'days');
        $this->text('user_uuid', 'user uuid');

    //      // Use 'saving' to set data before save
    // $form->saving(function ($form) {
    //     $enableIsTrue = config('your_config_file.enable_is_true');
    //     $form->model()->is_is_true = $enableIsTrue;
    // });

    // // Use 'saved' to perform actions after saving
    // $this->saved(function ($form) {
    //     // Example action after save
    //     admin_toastr('Saved successfully!', 'success');
    // });
    }
    // $form = new Form(new Reward());

}
