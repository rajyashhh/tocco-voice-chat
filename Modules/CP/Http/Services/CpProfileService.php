<?php

namespace Modules\CP\Http\Services;

use App\Repositories\WareRepository;
use App\Helpers\Common;
use App\Models\Pack;
use App\Models\Ware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\CP\Repositories\CpRepository;
use Modules\CP\Repositories\PackRepository;
use Modules\CP\Transformers\CpListResource;
use Modules\CP\Transformers\CpProfileResource;

class CpProfileService
{
    protected $cpRepository;
    protected $packRepository;

    public function __construct(CpRepository $cpRepository, PackRepository $packRepository)
    {
        $this->packRepository = $packRepository;
        $this->cpRepository = $cpRepository;
    }

    public function getCpProfiles($userId)
    {
        $statuses = [1, 4];
        $vipCount = $this->packRepository->countUserVipPacks($userId);

        if ($vipCount == 1) $count =  7;
        elseif ($vipCount >= 2) $count =  10;
        else $count =  4;
        $data = $this->cpRepository->getUserCpProfiles($userId, $statuses, $count);

        $pack = Pack::where('user_id', Auth::id())
            ->where('type', 100)
            ->where(function ($q) {
                $q->where('expire', 0)->orWhere('expire', '>=', time());
            })
            ->orderBy('target_id', 'desc')
            ->first();

        $seats = 3;

        if ($pack) {
            $ware = Ware::where('id', $pack->target_id)->first();
            if ($pack->use_num == 6) {
                $seats = 6;
                // $ware = Ware::where('num', 9)->where('type', 100)->first();
            } elseif ($pack->use_num == 3) {
                $seats = 3;
                // $ware = Ware::where('num', 6)->where('type', 100)->first();
            } elseif ($pack->use_num == 9) {
                $seats = 9;
                //  $ware = Ware::where('num', 12)->where('type', 100)->first();
            } elseif ($pack->use_num == 12) {
                $seats = 12;
                //  $ware = Ware::where('num', 15)->where('type', 100)->first();
            } else {
                $ware = null;
                $seats = 15;
            }
        } else {
            $ware = Ware::select('id', 'price', 'num')->where('type', 100)->where('get_type', 100)->where('num', 6)->first();
        }
        $mainCp = $data->firstWhere('relation.type', 'lovely') ?? null;

        $remainingCps = $mainCp ? $data->reject(function ($cp) use ($mainCp) {
            return $cp->id === $mainCp->id;
        }) : $data;

        $result = [
            'seats' => $seats,
            'wares' => $ware,
            'main_cp' => $mainCp ? new CpListResource($mainCp) : null,
            'remaining_cp' => $remainingCps ? CpProfileResource::collection($remainingCps) : []
        ];

        return Common::apiResponse(1, '', $result);
    }
}
