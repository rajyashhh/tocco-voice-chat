<?php

namespace Modules\SpecialId\Http\Controllers\Api;


use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use App\Models\Pack;
use App\Models\Ware;
use App\Models\Config;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\SpecialId\Entities\SpecialIdFram;
use Modules\SpecialId\Entities\SpecialHistory;
use Modules\Public\Http\Services\UpgradeLevelServices;
use Modules\SpecialId\Transformers\SpecialUsersRecourse;

class SpecialIdController extends Controller
{
    public function buySpecialId(Request $request)
    {
        $user       = $request->user();
        $special_id = $request->special_id;

        if (!$special_id) {
            return Common::apiResponse(false, 'missing params', null, 422);
        }

        // Check ware availability
        $ware = Ware::query()
            ->isNotUsedInPacks()
            ->where('id', $special_id)
            ->where('enable', 1)
            ->first();

        if (!$ware) {
            return Common::apiResponse(false, 'item not found or not for sale', null, 404);
        }

        $total_price = $ware->price;

        if ($user->di < $total_price) {
            return Common::apiResponse(false, 'Insufficient balance, please go to recharge!', null, 407);
        }

        // Check existing pack
        $pack = Pack::query()
            ->where('user_id', $user->id)
            ->where('target_id', $special_id)
            ->first();

        try {
            DB::beginTransaction();

            // if ($pack) {
            //     if ($pack->expire == 0) {
            //         return Common::apiResponse(false, 'you already have this item permanently', null, 405);
            //     }

            //     if (!is_null($pack->expire) && $pack->expire > now()->timestamp) {
            //         if ($ware->expire == 0) {
            //             return Common::apiResponse(false, 'you already have this item in your pack', null, 405);
            //         }

            //         // Extend expire
            //         $pack->expire = now()->addDays($ware->expire)->timestamp;
            //     } elseif (is_null($pack->expire)) {
            //         if ($ware->expire == 0) {
            //             return Common::apiResponse(false, 'you already have this item in your pack', null, 405);
            //         }

            //         // Add more days
            //         $pack->days += $ware->expire;
            //     } else {
            //         // expired => remove
            //         $pack->delete();
            //         $pack = null;
            //     }

            //     if ($pack) {
            //         $pack->price += $total_price;
            //         $pack->save();
            //     }
            // }

            // If no pack, create new
            
                $pack = Pack::create([
                    'user_id'       => $user->id,
                    'type'          => $ware->type,
                    'get_type'      => $ware->get_type,
                    'target_id'     => $ware->id,
                    'num'           => 1,
                    'days'          => $ware->expire,
                    'is_read'       => 1,
                    'use_num'       => $ware->num,
                    'price'         => $total_price,
                    'receive_type'  => 'buy-special-id',
                ]);
            

            // Deduct balance + log
            $this->deductAndLog($user, $total_price, $ware->name);

            DB::commit();

            // Upgrade service
            (new UpgradeLevelServices())->purchaseItem($user, $ware->exp);

            return Common::apiResponse(true, 'success process');
        } catch (\Exception $e) {
            DB::rollBack();
            return Common::apiResponse(false, 'an error occurred please try again later!', null, 400);
        }
    }

    /**
     * Deduct balance and log transaction
     */
    protected function deductAndLog($user, $amount, $wareName)
    {
        $amountBefore = $user->di;
        $logAmount    = -abs($amount);

        UserCoinLogHelper::logByType(
            $user->id,
            $logAmount,
            $amountBefore,
            UserCoinLogType::PACK,
            $wareName
        );

        $user->decrement('di', $amount);
    }


    public function usePackItem(Request $request)
    {
        $user    = $request->user();
        $item_id = $request->item_id;
        $status = $request->used ?? 0;
        if (!$item_id) return Common::apiResponse(0, 'missing params');

        $pack = Pack::query()
            ->where(['user_id' => $user->id])
            ->where('id', $item_id)->with('ware')
            ->first();


        if ($pack) {
            // Un use all packs
            /// TODO @m2led
            if ($status) {
                Pack::where('type', 25)
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $pack->id)
                    ->update(['is_used' => 0]);
            }
            if (is_null($pack->expire)) {
                $expire =    $pack->days ? now()->addDays($pack->days)->timestamp  : 0;
                $pack->update(['is_used' => $status, 'use_num' => 1, 'expire' => $expire]);
            } else {
                $pack->update(['is_used' => $status, 'use_num' => 1]);
            }


            SpecialHistory::where('user_id', $user->id)->where('ware_id', '!=', $pack->target_id)->update(['status' => 0]);
            $specialHistory =  SpecialHistory::where([
                'user_id' => $user->id,
                'ware_id' => $pack->target_id,
            ])->first();
            if (!$specialHistory) {


                SpecialHistory::create([
                    'status' => $status,
                    'user_id' => $user->id,
                    'ware_id' => $pack->target_id,
                ]);
            } else {
                $specialHistory->status = $status;
                $specialHistory->save();
            }


            $user->special_id = $status == 0 ? null : $pack->ware->value;
            $user->save();
            return Common::apiResponse(1, 'update successfully', ['target_id' => !$status ? null : $pack->target_id], 200);
        }
        return Common::apiResponse(0, 'item not found', null, 404);
    }

    public function upload_special_id(Request $request)
    {
        $user    = $request->user();
        $special_id = $request->value;
        if (!$special_id || !$request->frame_id) return Common::apiResponse(0, 'missing params', null, 422);
        $ware = Ware::query()->whereDoesntHave('ware_users')->where('value', $special_id)
            ->where('enable', 1)
            ->first();
        if ($ware) return Common::apiResponse(0, 'item exist before go to mall to buy it', null, 404);

        $pack        = Pack::query()->where("user_id", '!=', $user->id)->where('expire', '>=', now()->timestamp)->whereHas('ware', function ($q) use ($special_id) {
            $q->where("value", $special_id);
        })->first();
        if ($pack) return Common::apiResponse(0, 'item is used with anther user', null, 404);
        $total_price = Config::query()->where('name', 'upload_special_id_price')->first()?->value ?? 0;
        if ($user->di < $total_price) return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);

        $expire = Config::query()->where('name', 'custom_special_id_expire')->first()?->value ?? 30;

        $frame = SpecialIdFram::query()->find($request->fram_id);
        $check = Ware::query()->where('value', $special_id)->first();
        if ($check) {
            $checkRequest = \DB::table('user_ware')->where('ware_id', $check->id)->where('user_id', $user->id)->first();
            if ($checkRequest) {
                return Common::apiResponse(0, 'you have an request before', null, 405);
            }
        }
        $user->decrement('di', $total_price);

        $ware = Ware::updateOrCreate([
            'value' => $special_id,
        ], [
            'type' => 25,
            'get_type' => 4,
            'image' => @$frame->image,
            'expire' => $expire,
            'enable' => 0,
        ]);
        $ware->ware_users()->attach($user->id, ['disable' => false]);
        return Common::apiResponse(1, __("api_responses.request_sent"));
        //        $pack        = Pack::query()->where("user_id",$user->id)->where('expire', '>=', now()->timestamp)->whereHas('ware', function ($q) use ($special_id){
        //            $q->where("value",$special_id);
        //        })->first();
        //        if ($pack) {
        //            if ($pack->expire == 0) return Common::apiResponse(0, 'you have this item in your pack no need to buy it', null, 405);
        //            if ($pack->expire > now()->timestamp) {
        //                if ($ware->expire != 0) {
        //                    DB::beginTransaction();
        //                    try {
        //                        $pack->expire += ( $ware->expire * 86400);
        //                        $pack->price += $total_price;
        //                        $user->decrement('di', $total_price);
        //                        $pack->save();
        //                        $user->save();
        //                        DB::commit();
        //                        (new UpgradeLevelServices())->purchaseItem($user, $ware->exp);
        //                        return Common::apiResponse(1, 'success process');
        //                    } catch (\Exception $exception) {
        //                        DB::rollBack();
        //                        return Common::apiResponse(0, 'fail', null, 400);
        //                    }
        //                } else {
        //                    return Common::apiResponse(0, 'you have this item in your pack no need to buy it', null, 405);
        //                }
        //            } else {
        //                $pack->delete();
        //            }
        //        }
        //
        //        DB::beginTransaction();
        //        try {
        //            $arr['user_id']   = $user->id;
        //            $arr['type']      = $ware->type;
        //            $arr['get_type']  = $ware->get_type;
        //            $arr['target_id'] = $ware->id;
        //            $arr['num']       = 1; //$qty;
        //            $arr['expire']    = $ware->expire ? time() + ($ware->expire * 86400) : 0;
        //            $arr['is_read']   = 1;
        //            $arr['use_num']   = $ware->num;
        //            $arr['price']     = $total_price;
        //            $newPack=Pack::query()->create($arr);
        //            $user->decrement('di', $total_price);
        //            $ware->ware_users()->attach($user->id);
        //            DB::commit();
        //            (new UpgradeLevelServices())->purchaseItem($user, $ware->exp);
        //            return Common::apiResponse(1, 'success process');
        //        } catch (\Exception $exception) {
        //            DB::rollBack();
        //            return Common::apiResponse(0, 'an error occurred please try again later!', null, 400);
        //        }
    }

    public function specialIdFrame()
    {
        $data = SpecialIdFram::get();
        return Common::apiResponse(1, '', $data, 200);
    }

    public function specialUsers(Request $request)
    {
        $data = SpecialHistory::when(isset($request['startDate']) && $request['startDate'] != 'null' && isset($request['endDate']) && $request['endDate'] != 'null', function ($query) use ($request) {
            $query->whereBetween('created_at', [Carbon::createFromFormat('Y-m-d', $request['startDate'])->startOfDay(), Carbon::createFromFormat('Y-m-d', $request['endDate'])->endOfDay()]);
        })->when(isset($request['ware_id']) && $request['ware_id'] != 'null', function ($query) use ($request) {
            $query->where('ware_id', $request['ware_id']);
        })->where('status', 1)->with('user', 'ware')->get();

        return Common::apiResponse(1, '', SpecialUsersRecourse::collection($data), 200);
    }
}
