<?php

namespace App\Http\Controllers\utd;

use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use Modules\Vip\Entities\UserVip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Vip\Helpers\VipCommon;

class DedicateVipController extends Controller
{
    public function index(){
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $results = OVip::when($search, function($q)use($search){
            $q->where('id', $search);
        })
        ->paginate($perPage);

        return Common::apiResponse(true, 'Success', $results);
    }


    public function delete_all(Request $request){

        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        OVip::whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }


    public function dedicate($id, Request $request){

        $user = User::query()->searchByUuid($request->user_uuid)->first();
            // admin only put to user vip greater than 30 days
            /* if (!Admin::user()->can('*') && $request->days > 30){
                return $this->response()->error(__('dashboard.addAchivement'))->refresh();
            } */
           $vip = OVip::findOrFail($id);
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

                    VipCommon::createUserVip($vip ,$user ,$request->days ?? 1  , $request->dash_user_id ,'',1,0,0,'dash-dedicate');

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

                DB::commit();
                CustomNotification::vips($user, $request->days, $vip->img);
                return Common::apiResponse(true,__('dashboard.successful'));
            } catch (\Exception $exception) {
                echo($exception->getMessage());
                DB::rollBack();
                return Common::apiResponse(false,'خطا.');
            }

    }
}
