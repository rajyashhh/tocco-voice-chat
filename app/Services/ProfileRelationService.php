<?php

namespace App\Services;

use App\Repositories\ProfileRepository;
use App\Models\Follow;
use App\Models\User;
use Modules\Vip\Entities\Vip;
use App\Repositories\User\UserRepository;

class ProfileRelationService
{

    public function getHelperArrays(User $user, $data): array
    {
        $userFollowers      = Follow::query()->where('user_id',$user->id)->pluck('followed_user_id')->toArray();


        [$vipsSenderImages, $vipsReceivedImages] =
            $this->getLevelsSenderAndReceiver($data);

        return [$userFollowers, $vipsSenderImages, $vipsReceivedImages];
    }

    public function getLevelsSenderAndReceiver($data): array
    {
        $vipsSenderImages   = $data->pluck('total_sender_level');
        $vipsReceivedImages = $data->pluck('total_received_level');

        $vipsSenderImages   = $this->getLevel($vipsSenderImages, 2);
        $vipsReceivedImages = $this->getLevel($vipsReceivedImages);
        return [$vipsSenderImages, $vipsReceivedImages];
    }

    public function getLevel($levelsList, $type = 1)
    {
        return Vip::collectionBuilder()->whereIn("level", $levelsList)->where('type', $type)->get();

//        return Vip::query()->whereIn('level', $levelsList)
//                  ->where('type', $type)->select('img', 'level')->get();
    }
}
