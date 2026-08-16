<?php

namespace App\Tik\Services;

use App\Enums\UserCoinLogType;
use Exception;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\PackRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\WareRepository;
use Modules\Public\Http\Services\UserCounterServices;
use Modules\Public\Http\Services\UpgradeLevelServices;

class MallService
{
    public function __construct(
        private readonly PackRepository $packRepository,
        private readonly WareRepository $wareRepository,
        private readonly UserRepository $userRepository,

    ) {}

    public function getWares($userId, $type)
    {
        return $this->wareRepository->all($userId, $type);
    }

    public function getAllWares($type, $userId = null)
    {
        return $this->wareRepository->allWithType($type, $userId);
    }

    public function buyWares($user, $wareId, $quantity)
    {
        $ware = $this->wareRepository->getById($wareId);

        if (!$ware) return Common::apiResponse(0, 'item not found or not for sale', null, 404);
        if ($ware->level) {
            $userVipLevel = Common::userVipLevel($user->id, $ware->level);
            if (!$userVipLevel) return Common::apiResponse(0, __("api.buyVip", ['level' => $ware->level]), null, 404);
        }

        $pack = $this->packRepository->userPack($user->id, $ware->id);

        $totalPrice = $ware->price * $quantity;
        if ($user->di < $totalPrice) return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);

        if ($pack) {
            return  $this->updatePack($user, $pack, $ware, $quantity, $totalPrice, 'buy');
        } else {
            try {
                // no pack so create it
                $data = [
                    'user_id'   => $user->id,
                    'type'      => $ware->type,
                    'get_type'  => $ware->get_type,
                    'target_id' => $ware->id,
                    'num'       => $quantity, //$qty,
                    'is_read'   => 1,
                    'use_num'   => $ware->num,
                    'price'     => $totalPrice,
                    'price_item' => $ware->price,
                    'receive_type' => 'buy-ware',
                    'days' => $ware->expire ?  $ware->expire  : 0,
                ];
                $this->packRepository->create($data);

                $logAmount = -abs($totalPrice);
                $amountBefore =  $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $logAmount,
                    $amountBefore,
                    UserCoinLogType::PACK,
                    $ware->name
                );

                $this->service($user, $ware->exp, $totalPrice, 'buy');
                return Common::apiResponse(1, 'success process');
            } catch (Exception $exception) {
                return Common::apiResponse(0, $exception->getMessage(), null, 400);
            }
        }
    }

    public function sendWare($auth, $wareId, $userId, $quantity)
    {

        $toUser = $this->userRepository->findById($userId);
        if (!$toUser) return Common::apiResponse(0, 'receiver user not found', null, 404);

        $ware = $this->wareRepository->getById($wareId);

        if (!$ware) return Common::apiResponse(0, 'item not found or not for sale', null, 404);
        $pack        = $this->packRepository->userPack($toUser->id, $ware->id);

        $totalPrice = $ware->price * $quantity;
        if ($auth->di < $totalPrice) return Common::apiResponse(0, 'Insufficient balance, please go to recharge!', null, 407);
        if ($pack) {
            $this->updatePack($auth, $pack, $ware, $quantity, $totalPrice, 'send');
        }

        DB::beginTransaction();
        try {

            $data = [
                'user_id'   =>  $toUser->id,
                'type'      => $ware->type,
                'get_type'  => $ware->get_type,
                'target_id' => $ware->id,
                'num'       => 1, 
                'expire'    => null,
                'days'      => $ware->expire,
                'sender_id'      => $auth->id,
                'is_read'   => 1,
                'use_num'   => $ware->num,
                'price'     => $totalPrice,
                'receive_type' => 'send-ware',
                
            ];
            $this->packRepository->create($data);

            $logAmount = -abs($totalPrice);
            $amountBefore =  $auth->di;
            UserCoinLogHelper::logByType(
                $auth->id,
                $logAmount,
                $amountBefore,
                UserCoinLogType::PACK,
                $ware->name
            );

            $this->service($auth, $ware->exp, $totalPrice, 'send');

            DB::commit();
            return Common::apiResponse(1, 'success process');
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, 'an error occurred please try again later!', null, 400);
        }
    }

    public function updatePack($user, $pack, $ware, $quantity, $totalPrice, $type)
    {
        // If pack is permanent (expire = 0)
        if ($pack->expire === 0) {
            return Common::apiResponse(0, 'you have this item in your pack no need to buy it', null, 405);
        }

        // If pack is still valid (not expired)
        if (!is_null($pack->expire) && $pack->expire > now()->timestamp) {
            if ($ware->expire == 0) {
                return Common::apiResponse(0, 'you have this item in your pack no need to buy it', null, 405);
            }

            return $this->extendActivePack($pack, $ware, $quantity, $totalPrice, $user, $type);
        } elseif (is_null($pack->expire)) {
            if ($ware->expire == 0) {
                return Common::apiResponse(0, 'you have this item in your pack no need to buy it', null, 405);
            }

            return $this->updatePackWithoutExpire($pack, $ware, $quantity, $totalPrice, $user, $type);
        }

        // If expired: remove it
        $this->packRepository->delete($pack);
    }
    protected function extendActivePack($pack, $ware, $quantity, $totalPrice, $user, $type)
    {
        try {
            $expireSeconds = $quantity * $ware->expire * 86400;

            $updateData = [
                'num'    => $pack->num + $quantity,
                'expire' => $pack->expire + $expireSeconds,
                'price'  => $pack->price + $totalPrice,
            ];

            $this->packRepository->update($updateData, $pack->id);
            $this->service($user, $ware->exp, $totalPrice, $type);

            return Common::apiResponse(1, 'success process');
        } catch (Exception $e) {
            return Common::apiResponse(0, 'fail', null, 400);
        }
    }

    protected function updatePackWithoutExpire($pack, $ware, $quantity, $totalPrice, $user, $type)
    {
        try {
            $updateData = [
                'num'    => $pack->num + $quantity,
                'price'  => $pack->price + $totalPrice,
                'days'   => $pack->days + $ware->expire,
            ];

            $this->packRepository->update($updateData, $pack->id);
            $this->service($user, $ware->exp, $totalPrice, $type);

            return Common::apiResponse(1, 'success process');
        } catch (Exception $e) {
            return Common::apiResponse(0, 'fail', null, 400);
        }
    }




    public function service($user, $wareExp, $totalPrice, $type)
    {
        if ($type == 'buy') {
            (new UpgradeLevelServices())->purchaseItem($user, $totalPrice);
            (new UserCounterServices)->eventUser($user, 'mybag', 1);
        }

        $this->userRepository->decrementUserCoins($user, $totalPrice);
    }

    public function bestSaleWare()
    {
        return $this->packRepository->bestSale();
    }

    public function giftOVip($level, $type)
    {
        return $this->wareRepository->giftOVip($level, $type);
    }

    public function ware($type)
    {
        return $this->wareRepository->all(0, $type);
    }

    public function getWabbles()
    {
        return $this->wareRepository->getAllFromType(12);
    }

    public function warePadding($id)
    {
        return $this->wareRepository->getDressWare($id);
    }
}
