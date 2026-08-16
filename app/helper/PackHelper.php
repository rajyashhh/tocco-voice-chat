<?php

namespace App\Helpers;

use App\Models\Pack;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PackHelper
{
    public static function createPack(User $user, $ware, $request = null, $qty = 1): Pack
    {
        $arr = [];
        $types = [8, 9, 12, 14, 17];

        $arr['user_id']    = $user->id;
        $arr['type']       = $ware->type;
        $arr['get_type']   = $ware->get_type;
        $arr['target_id']  = $ware->id;
        $arr['num']        = $qty;
        $arr['is_read']    = 1;
        $arr['dash_user_id'] = auth()->id();

        if ($request && $request->days) {
            $arr['expire'] = time() + (($request->days ?? $ware->expire) * 86400);
            $arr['days']   = $request->days ?? $ware->expire;
        } else {
            $arr['expire'] = $ware->expire ? time() + ($ware->expire * 86400) : 0;
            $arr['days']   = $ware->expire ?? 0;
        }

        if (in_array($ware->type, $types)) {
            $arr['is_used'] = 1;
            $arr['using']   = 1;
        } else {
            $enableVipAuto  = Common::getConf('enable_vip_auto') ?? "false";
            $arr['is_used'] = $enableVipAuto === "true" ? 1 : 0;
            $arr['using']   = 0;
        }

        DB::beginTransaction();
        $pack = Pack::query()->create($arr);

        if ($ware->type == 25) {
            $user->special_id = $ware->value;
            $user->save();
        }

        DB::commit();
        return $pack;
    }
}
