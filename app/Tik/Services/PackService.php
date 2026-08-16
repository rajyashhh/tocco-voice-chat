<?php

namespace App\Tik\Services;

use Exception;

use App\Models\User;
use App\Helpers\Common;
use App\Tik\Repositories\PackRepository;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\WareRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Vip\Repositories\UserVipRepository;
use phpDocumentor\Reflection\Types\Mixed_;


class PackService
{
    public function __construct(
        private readonly PackRepository $packRepository,
        private readonly WareRepository $wareRepository,
        private readonly RoomRepository $roomRepository,
        private readonly UserVipRepository $userVipRepository,
        private readonly UserRepository $userRepository,
    ) {}


    public function changePackMode($type, $privilegeArr, User $user, $isAvailable)
    {
        if (key_exists($type, $privilegeArr)) {
            $privilegeId = $privilegeArr[$type];
            $isWare = $this->wareRepository->checkWare($privilegeId);
            if ($isAvailable && !$isWare) {
                throw new Exception('not found');
            } else if (!$this->packRepository->checkPack($user->id, $privilegeId, $isAvailable)) {

                throw new Exception('not allowed');
            }
            $this->packRepository->changeAvailabilityAllPack($user->id, $privilegeId, $isAvailable);
            switch ($type) {
                case 'country':
                    /*if ($isAvailable) {
                        $user->country_id = null;
                        $user->save();
                    }*/
                    break;
                case 'room':
                    $this->roomRepository->updateRoomStatus($user->id, $isAvailable);
                    break;
            }
        }
    }


    public function userPack($request): mixed
    {
        $this->packRepository->deleteExpirePack();
        $userId = $request->user_id ?:  $request->user()->id;
        $type = $request->type;
        if (!in_array($type, [1, 2, 3, 4, 5, 6, 7, 25, 22, 28])) throw new \Exception('type not found');
        if ($type == 2) {
            $data = $this->packRepository->packsJoinWithGift($userId, $type, ['ware']);
        } elseif ($type == 22) {
            $this->userVipRepository->deleteExpireUserVip();
            $data = $this->userVipRepository->getByUserId($userId, ['OVip']);
        } else {
            $data = $this->packRepository->packsJoinWithWare($userId, $type, ['user']);
        }
        return  $data;
    }


    public function usedPack($user, $itemId)
    {
        $supportedTypes = [4, 5, 6, 7, 28];
        $dressTypeMap = [
            4 => 1,
            5 => 2,
            6 => 3,
            7 => 4,
        ];

        $pack = $this->packRepository->getByUserId($user->id, $itemId);

        if (!$pack) {
            throw new Exception('Item not found');
        }

        if (!in_array($pack->type, $supportedTypes)) {
            throw new Exception('Unusable item');
        }

        // Mark all same type packs as unused
        $this->packRepository->updateIsUsedByType($user->id, $pack->type);

        // Calculate expire timestamp
        $expire = $pack->expire ? $pack->expire : ($pack->days ? now()->addDays($pack->days)->timestamp : 0);

        // Mark selected pack as used
        $this->packRepository->updateIsUsedByPackId($user->id, $itemId, $expire);

        // If applicable, update user dress
        if (isset($dressTypeMap[$pack->type])) {
            $this->userRepository->updateDress($user, $dressTypeMap, $pack->type, $pack->target_id);
        }

        return $pack->target_id;
    }



    public function updateDress($user, $type, $itemId)
    {
        $this->userRepository->nullDress($user, $type);
        if ($itemId) $this->packRepository->update(['is_used' => 0], $itemId);

        return true;
    }
}
