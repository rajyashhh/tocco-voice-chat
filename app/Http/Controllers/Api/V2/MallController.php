<?php

namespace App\Http\Controllers\Api\V2;

use Carbon\Carbon;
use App\Models\Ware;
use App\Helpers\Common;
use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Request;
use App\Tik\Services\MallService;
use App\Http\Controllers\Controller;
use App\Http\Resources\WareResource;
use App\Http\Resources\WareResourceAll;
use App\Http\Resources\WarePaddingResource;
use App\Http\Resources\BestWareSaleResource;
use App\Models\Pack;
use Modules\Public\Http\Services\UserCounterServices;


class MallController extends Controller
{
    // wares

    public function __construct(private MallService $mallService) {}

    public function index(Request $request)
    {
        $user = $request->user();
        (new UserCounterServices)->UpgradeDateForType($user, "mall");
        if (!$request->type) {
            return Common::apiResponse(false, 'type is required', null, 422);
        }

        $wares = $this->mallService->getWares($user->id, $request->type,);

        return Common::apiResponse(true, '', WareResource::collection($wares), 200);
    }

    public function padding()
    {
        $wares = $this->mallService->getAllWares(5);

        return Common::apiResponse(true, '',   WarePaddingResource::collection($wares), 200);
    }

    public function wabbleWare(Request $request)
    {
        $type = $request->type ?? 12;
        $bubble = $this->mallService->ware($type);
        return Common::apiResponse(true, '', WareResource::collection($bubble), 200);
    }

    public function wabbleAll()
    {
        $bubble = $this->mallService->getWabbles();
        $resource = WareResourceAll::collection($bubble);
        $merged = $resource->toArray(request()); // مهم تمرر request() هنا
        $merged[] = [
            'id' => 0,
            'image_type' => 'png',
            'key_json' => (object)[],
            'image' => 'wappel.png',
            'img' => 'wappel.png',
        ];
        return Common::apiResponse(true, '', $merged, 200);
    }

    public function buyWare(Request $request)
    {
        $user    = $request->user();
        $wareId = $request->ware_id;
        $quantity     = $request->qty ?: 1;
        return $this->mallService->buyWares($user, $wareId, $quantity);
    }

    public function sendWare(Request $request)
    {
        $user = $request->user();
        $toUserId = $request->to_id;


        $wareId = $request->ware_id;
        $quantity = $request->qty ?: 1;
        if (!$wareId || !$toUserId) return Common::apiResponse(0, 'missing params', null, 422);

        return $this->mallService->sendWare($user, $wareId, $toUserId, $quantity);
    }


    public function wareImage()
    {
        $wares = Ware::whereNotNull('img2')->get();
        foreach ($wares as $wares) {
            $ImageType =     pathinfo($wares->img2, PATHINFO_EXTENSION);
            $wares->image_type = $ImageType == 'alpha' ? 'mp4' : $ImageType;
            $wares->save();
        }
        return $wares;
    }

    public function bestWareSale()
    {
        $pestSaleProduct = $this->mallService->bestSaleWare();
        return Common::apiResponse(true, '', BestWareSaleResource::collection($pestSaleProduct), 200);
    }

    public function giftOVip(Request $request)
    {
        try {
            $ware = $this->mallService->giftOVip($request->level, $request->type);

            if (!$ware) {
                return response()->json([
                    'error' => 'No gift found for the specified level and type'
                ], 404);
            }

            return response()->json([
                'image_url' => getImagePath($ware->show_img),
                'title' => $ware->title
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateExpireUserVip()
    {
        $userVips = UserVip::where('expire', 0)->with('packs')->get();
        
        if ($userVips) {
            foreach ($userVips as $userVip) {
                $expires = $userVip->packs->pluck('expire')->filter(function ($value) {
                    return $value !== null && $value != 0;
                });
                if ($expires->isNotEmpty()) {
                    $userVip->update(['expire' => $expires->first()]);
                }else{
                    $userVip->delete();
                }
            }
        }

        $expireUserVips = UserVip::where('expire', '<', Carbon::now()->timestamp)
            ->whereHas('packs', function ($q) {
                $q->where(function ($query) {
                    $query->where('expire', '>', Carbon::now()->timestamp)
                        ->orWhere('expire', 0)->orWhere('expire', null);
                });
            })
            ->where('expire', '!=', 0)
            ->whereNotNull('expire')
            ->with('packs')
            ->get();

        $expireUpdates = $expireUserVips->groupBy('expire');
        foreach ($expireUpdates as $expireValue => $vips) {
            $vipIds = $vips->pluck('id')->toArray();
            // whereIn() already ignores NULL values, no need for redundant != null check
            Pack::whereIn('vip_user_id', $vipIds)
                ->update(['expire' => $expireValue]);
        }

        Pack::whereNull('vip_user_id')->where('get_type',1)->delete();
        Pack::whereNull('expire')->where('days',0)->delete();


        return 'done';
    }
}
