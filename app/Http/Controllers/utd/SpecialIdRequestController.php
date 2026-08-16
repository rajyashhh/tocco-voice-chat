<?php

namespace App\Http\Controllers\utd;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserWareResource;
use App\Models\Config;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Public\Http\Services\UpgradeLevelServices;
use Modules\SpecialId\Entities\UserWare;

class SpecialIdRequestController extends Controller
{
    public function index()
    {
        $search = request('search');
        $perPage = request('per_page') ?? 10;

        $result = UserWare::with('user', 'ware')
            ->when($search, function ($q) use($search) {
                $q->where('id', $search);
            })
            ->where('disable', 0)
            ->orderBy('id','desc')->paginate($perPage);

        return Common::apiResponse(true, 'Success', UserWareResource::collection($result));
    }

    public function show($id){

        $result = UserWare::with('user', 'ware')->findOrFail($id);

        return Common::apiResponse(true, 'Success', new UserWareResource($result));
    }

    public function update($id, Request $request){

        $request->validate([
            'disable' => 'required|integer|in:0,1,2',
        ]);

        $userWare = UserWare::findOrFail($id);
        $ware_id = $userWare->ware_id;
        $user_id = $userWare->user_id;
        $status = $request->input('disable');

        if ($status == 2) {
            $status = 0;
        }

        DB::beginTransaction();
        try {
            // Update user_ware disable status
            $userWare->update(['disable' => $status]);

            $user = User::find($user_id);
            $ware = Ware::find($ware_id);
            $total_price = Config::where('name', 'upload_special_id_price')->first()?->value ?? 0;

            if ($user && $ware && $status == 1) {
                // Enable the ware
                $ware->update(['enable' => 1]);

                // Create Pack entry
                $packData = [
                    'user_id'   => $user->id,
                    'type'      => $ware->type,
                    'get_type'  => $ware->get_type,
                    'target_id' => $ware->id,
                    'num'       => 1,
                    'expire'    => $ware->expire ? time() + ($ware->expire * 86400) : 0,
                    'is_read'   => 1,
                    'use_num'   => $ware->num,
                    'price'     => $total_price,
                    'receive_type'     => 'special-id-update',
                ];
                Pack::create($packData);

              
                // Upgrade user level
                (new UpgradeLevelServices())->purchaseItem($user, $ware->exp);
            } else {

                $amountBefore =  $user->di;
                $logAmount = -abs($total_price);
                UserCoinLogHelper::logByType(
                    $user->id,
                    $logAmount,
                    $amountBefore,
                    UserCoinLogType::PACK,
                    'Special Id'
                );
                $user->increment('di', $total_price);
            }

            DB::commit();
            return Common::apiResponse(true,'UserWare updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return Common::apiResponse(false,'Failed to update UserWare');
        }
    }

    public function delete($id){
        UserWare::where('disable', 0)->findOrFail($id)->delete();
        return Common::apiResponse(true,'Success');
    }

    public function delete_all(Request $request){
        $request->validate([
            'ids' => 'required'
        ]);

        $ids = explode(',', $request->ids);

        UserWare::where('disable', 0)->whereIn('id', $ids)->delete();

        return Common::apiResponse(true,'Success');
    }
}
