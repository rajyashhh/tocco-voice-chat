<?php

namespace App\Tik\Services;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\RequestBackgroundImageRepository;

class RequestBackgroundImagService
{
    public function __construct(
        private readonly RequestBackgroundImageRepository $requestBackgroundImageRepository,
        private readonly UserRepository $userRepository,

    ) {}

    public function create($request, $userId, $price)
    {

        if ($request->hasFile('image')) {
            $img                                   = $request->file('image');
            $image                                 = Common::upload('images', $img);
            $expire = Common::getConfig('background_expiration') ?? 1;
            $data = [
                'owner_room_id' => $userId,
                'img' => $image,
                'price' => $price,
                'status' => 1,
                'expair' => now()->addDays($expire)->timestamp,
            ];
            $this->requestBackgroundImageRepository->create($data);

            $amountBefore =  Common::getCurrentBalance($userId);
            $logAmount = -abs($price);
            UserCoinLogHelper::logByType(
                $userId,
                $logAmount,
                $amountBefore,
                UserCoinLogType::BACKGROUND_IMAGES,
                $request->name
            );

            $this->userRepository->decrementCoins($userId, $price);
            return $image;
        }
    }

    public function findByUserId($id)
    {
        return $this->requestBackgroundImageRepository->findByUserId($id);
    }

    public function show($id)
    {
        return $this->requestBackgroundImageRepository->findOrFail($id);
    }

    public function index($id, $perPage, $page)
    {
        return $this->requestBackgroundImageRepository->all($id, $perPage, $page);
    }

    public function createDash($request)
    {
        if ($request->hasFile('img')) {
            $img = Common::upload('images', $request->file('img'));
        }

        $data = [
            'type' => 'admin',
            'img' => $img ?? null,
            'owner_room_id' => $request->owner_room_id,
            'status' => $request->status,
            'expair' => $request->expair,
        ];


        $this->requestBackgroundImageRepository->store($data);
        return true;
    }

    public function update($id, $request)
    {
        if ($request->hasFile('img')) {
            $img = Common::upload('images', $request->file('img'));
        } else {
            $img = $request->img;
        }


        $data = [
            'type' => 'admin',
            'img' => $img,
            'owner_room_id' => $request->owner_room_id,
            'status' => $request->status,
            'expair' => $request->expair,
        ];
        $this->requestBackgroundImageRepository->update($data, $id);
        return true;
    }

    public function delete($id)
    {
        return  $this->requestBackgroundImageRepository->delete($id);
    }
}
