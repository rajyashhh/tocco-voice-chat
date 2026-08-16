<?php

namespace Modules\CP\Http\Services;

use App\Helpers\UserCoinLogHelper;
use App\Repositories\WareRepository;
use App\Helpers\Common;
use App\Models\Pack;
use Illuminate\Support\Facades\Auth;
use Modules\CP\Repositories\CpRepository;
use Modules\CP\Repositories\PackRepository;

class ExtendCardService
{
    protected $cpRepository;
    protected $packRepository;

    public function __construct(CpRepository $cpRepository, PackRepository $packRepository)
    {
        $this->packRepository = $packRepository;
        $this->cpRepository = $cpRepository;
    }

    public function extendCard($user, $wareId)
    {
        if (!$wareId) {
            return Common::apiResponse(0, 'invalid_data');
        }

        $ware = $this->cpRepository->findWare($wareId);
        if (!$ware) {
            return Common::apiResponse(0, 'ware_not_found');
        }

        if ($ware->price > $user->di) {
            return Common::apiResponse(0, 'insufficient_di');
        }

        $expire = 30;

        /// TODO check expire packs
        $existingPack = $this->packRepository->findByUserIdAndTargetId($user->id, $ware->id);
        if ($existingPack && $existingPack->use_num == 15) return Common::apiResponse(0, 'all_chairs_purchased');

      //  $countPack = $this->packRepository->countUserVipPacks($user->id);

        // card ends today at midnight
        /// create another pack // from today to 30 days
        //if ($existingPack && $countPack == 2) {
        if ($existingPack) {
            $existingPack->expire = $existingPack->expire ? now()->timestamp + ($expire * 86400) : now()->addDays($expire)->timestamp;
            $existingPack->use_num = $existingPack->use_num + 3;
            $existingPack->save();
        } else {
            $this->packRepository->createPack([
                'user_id' => $user->id,
                'get_type' => $ware->get_type,
                'type' => $ware->type,
                'target_id' => $ware->id,
                'num' => 1,
                'expire' => $ware->expire ? now()->addDays($expire)->timestamp : 0,
                'use_num' => $ware->num,
                'receive_type' => 'extend-card-ware',

            ]);
      
        }


        $user->di -= $ware->price;
        $user->save();

        return Common::apiResponse(1, 'added_successfully');
    }
}
