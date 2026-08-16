<?php

namespace App\Services;


use Exception;
use Carbon\Carbon;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Log;
//use App\Tik\Repositories\VipRepository;
//use App\Tik\Repositories\OvipRepository;
use App\Tik\Repositories\PackRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\WareRepository;
//use App\Tik\Repositories\UserVipRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Vip\Repositories\OvipRepository;
use Modules\Vip\Repositories\UserVipRepository;
use Modules\Vip\Repositories\VipPrivilegeRepository;
use Modules\Vip\Repositories\VipRepository;

//use App\Tik\Repositories\VipPrivilegeRepository;

class VipService
{
    public function __construct(
        private readonly VipRepository $vipRepository,
        private readonly OvipRepository $ovipRepository,
        private readonly VipPrivilegeRepository $vipPrivilegeRepository,
        private readonly UserRepository $userRepository,
        private readonly UserVipRepository $userVipRepository,
        private readonly PackRepository $packRepository,
        private readonly WareRepository $wareRepository,

    ) {}

    public function vipIndex($type)
    {
        return $this->vipRepository->getByType($type);
    }

    public function vipList()
    {
        $vipPrivileges = $this->vipPrivilegeRepository->all();
        $oVips = $this->ovipRepository->getBySortLevel();


        $wares = $this->wareRepository->getOVip($oVips->pluck('level'), $vipPrivileges->pluck('type'));
        $oVips = $oVips->map(function ($oVip) use ($wares) {
            $filteredWares = $wares->where('level', $oVip->level);
            $oVip->setRelation('wares', $filteredWares);
            return $oVip;
        });


        $data = [
            'all_privileges' => $vipPrivileges,
            'o_vips' => $oVips,
        ];
        return $data;
    }

    public function buyVip($request)
    {
        $vip = $this->ovipRepository->findById($request->vip_id);
        if (!$vip) throw new \Exception('not found');
        $qty = $request->qty ?: 1;
        $total = $vip->price * $qty;
        $expire = $vip->expire;
        if ($expire == 0) {
            $ex = 0;
        } else {
            $ex = now()->addDays($expire * $qty)->timestamp;
        }
        if ($request->type == 1) {
            $type = 1;
            if (!$request->to_user) throw new \Exception('missing param');
            $userUuId = $request->to_user;
            $user = $this->userRepository->searchUser($userUuId);
            if (!$user) throw new \Exception('not found');
            if ($user->phone == null || $user->phone == '') throw new \Exception(__('api.phone'));
            $user_id = $user->id;
            $sender = $request->user();
            $sender_id = $sender->id;
            $from = $sender;
            if ($user->id == $from->id)  throw new \Exception(__("api_responses.notSend"));
            if ($sender->di < $total) throw new \Exception('balance low');
        } else {
            $type = 0;
            $user = $request->user();
            $user_id = $user->id;
            $sender_id = 0;
            if ($user->di < $total) throw new \Exception('balance low');
            $from = $user;
        }
        $this->userRepository->decrementUserCoins($from, $total);
        $this->packRepository->deleteExpirePack();
        $this->packRepository->unUseOldPack($user_id);

        $data = [
            'type' => $type,
            'sender_id' => $sender_id,
            'user_id' => $user_id,
            'vip_id' => $vip->id,
            'level' => $vip->level,
            'expire' => null,
            'qty' => $qty,
            'price' => $vip->price,
            'total' => $total,
            'is_used' => 0,
            'days' => $vip->expire,
        ];
        $this->userVipRepository->create($data);
        $countWares = $this->wareRepository->countWareByLevel($vip->level);
        return [$user, $countWares, $request->user(), $vip->price];
    }

    public function userVip($request)
    {
        $user_vip = $this->userVipRepository->findByIdWithOVip($request->vip_id);

        if (!$user_vip)  throw new \Exception(__("api_responses.vip_not_found"));

        $user = $request->user();

        $isUsed = (bool)$request->type;

        if (!$isUsed) $this->userVipRepository->updateIsUsedForUser($user->id);

        // update is used
        // $this->userVipRepository->updateIsUsedWithNum($user_vip, $isUsed);
         $user_vip->num_used += 1;
        $data = [
            'num_used' =>  $user_vip->num_used,
            'is_used' => $isUsed,
            'using'  => 1,
        ];
        if ($user_vip->using == 0) {
            $data['expire'] = ($user_vip->days == 0) ? 0 : now()->addDays($user_vip->days * $user_vip->qty)->timestamp;
        }
        $this->userVipRepository->update($data, $user_vip->id);
        if ($isUsed) $this->userVipRepository->updateTrueIsUsedForUser($user->id);

        $user_vip = $this->userVipRepository->findByIdWithOVip($request->vip_id);

        $vip = $user_vip->OVip;
        // if ($user_vip->num_used <= 1) {
        // add vip data to user
        Common::handelVip($vip, $user, null, $user_vip);
        // }
        return  $data['target_id'] = $user_vip->id;
    }

    public function usePack($request)
    {
        $user = $request->user();
        $isUsed = (bool)$request->type;
        return $this->userVipRepository->togglePackUsage($request->pack_id, $user->id, $isUsed);

    }




    public function sendVip($request)
    {
        $from = $request->user();
        $user_vip = $this->userVipRepository->findById($request->vip_id);
        if (!$user_vip || $user_vip->user_id != $from->id)  throw new \Exception(__("api_responses.vip_not_found"));

        if ($user_vip->is_used == 1  || $user_vip->num_used >= 1 || $user_vip->using == 1) throw new \Exception('ال vip مستخدم من قبل لا يمكن اهدائه');
        $user = $this->userRepository->searchUser($request->user_id);
        if (!$user) throw new \Exception('api_responses.notFound');
        if ($user->id == $from->id)  throw new \Exception(__("api_responses.notSend"));
        $data = [
            'sender_id' => $from->id,
            'user_id' => $user->id,
        ];
        $user_vip->update($data);

        return $user_vip;
    }

    public function createWareVip($request)
    {
        $vipPrivilege  = $this->vipPrivilegeRepository->findById($request->vipPrivilege_id);
        $Vip = $this->ovipRepository->findById($request->ovip_id);

        if ($request->hasFile('image')) {
            $image = Common::upload('images', $request->file('image'));
        }
        if ($request->hasFile('img2')) {
            $img2 = Common::upload('images', $request->file('image'));
        }
        $data = [
            'get_type' => 1,
            'type' => $vipPrivilege->type,
            'price' => 0,
            'name' => $request->name,
            'name_en' => $request->name_en,
            'title' => $request->title,
            'title_en' => $request->title_en,
            'level' => $Vip->level,
            'show_img' => $image,
            'img2' => $img2,
            'image_type' => $request->img2_type,
            'enable' => 1,
            'is_active_for_vip' => 1,

        ];

        $ware = $this->wareRepository->findById($request->ware_id);
        if (!$ware) {
            $this->wareRepository->create($data);
        } else {
            $this->wareRepository->update($data, $ware->id);
        }

        return true;
    }

    public function wareVip($request)
    {
        $vipPrivilege  = $this->vipPrivilegeRepository->findById($request->vipPrivilege_id);
        $Vip = $this->ovipRepository->findById($request->ovip_id);
        return $this->wareRepository->getByTypeAndLevel($vipPrivilege->type, $Vip->level);
    }

    public function badges($type)
    {
        return $this->vipRepository->badgesVip($type);
    }

    public function deleteWare($wareId)
    {
        $ware = $this->wareRepository->findById($wareId);
        $ware->delete();
        return true;
    }


    public function buyVipWithActive($request)
    {
        $vip = $this->ovipRepository->findById($request->vip_id);
        if (!$vip) return Common::apiResponse(0, __('api_responses.not_found'), null, 404);
        $qty = $request->qty ?: 1;
        $total = $vip->price * $qty;
        $expire = $vip->expire;
        $expire == 0 ? $ex = 0 : $ex = now()->addDays($expire * $qty)->timestamp;
        try {
            if ($request->type == 1) {
                [$user_id, $from, $type, $sender_id, $user] = $this->authUserSend($request, $total);
            } else {
                [$user_id, $from, $type, $sender_id, $user] = $this->authUser($request, $total);
            }
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage());
        }

        DB::beginTransaction();
        try {
            $from->decrement('di', $total);

            $this->packRepository->deleteExpirePack();

            $userVip = $this->userVipRepository->findByUserLevel($user_id, $vip->level, $vip->id);
            if ($userVip) {

                if ($userVip->expire == 0) {
                    $ex = 0;
                } else {
                    $ex =  $userVip->expire + ($expire * $qty * 86400);
                }

                $data = [
                    'expire'   => $ex,
                    'qty'      => $userVip->qty + $qty,
                    'total'    => $userVip->total + $total,
                    'is_used'  => 1,

                ];
                $this->userVipRepository->update($data, $userVip->id);
            } else {
                $data = [
                    'type' => $type,
                    'sender_id' => $sender_id,
                    'user_id' => $user_id,
                    'vip_id' => $vip->id,
                    'level' => $vip->level,
                    'expire' => $ex,
                    'qty' => $qty,
                    'price' => $vip->price,
                    'total' => $total,
                    'is_used' => 1,
                ];

                $data = $this->userVipRepository->create($data);
            }
            Common::handelVip($vip, $user, null, userVip: $userVip);
            DB::commit();
            $ex = Carbon::parse($ex)->diffInDays(now());
            CustomNotification::vips($user, $ex, $vip->img);
            return Common::apiResponse(1, 'done', null, 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function buyVips($request)
    {
        $vip = $this->ovipRepository->findById($request->vip_id);
        if (!$vip) return Common::apiResponse(0, __('api_responses.not_found'), null, 404);
        $qty = $request->qty ?: 1;
        $total = $vip->price * $qty;
        $expire = $vip->expire;
        $expire == 0 ? $ex = 0 : $ex = now()->addDays($expire * $qty)->timestamp;
        try {
            if ($request->type == 1) {
                [$user_id, $from, $type, $sender_id, $user] = $this->authUserSend($request, $total);
            } else {
                [$user_id, $from, $type, $sender_id, $user] = $this->authUser($request, $total);
            }
        } catch (Exception $e) {
            return Common::apiResponse(0, $e->getMessage());
        }

        DB::beginTransaction();
        try {
            $from->decrement('di', $total);

            $this->packRepository->deleteExpirePack();

            // $userVip = $this->userVipRepository->findByUserLevel($user_id, $vip->level, $vip->id);
            // if ($userVip) {

            //     if ($userVip->expire == 0) {
            //         $ex = 0;
            //     } else {
            //         $ex =  $userVip->expire + ($expire * $qty * 86400);
            //     }

            //     $data = [
            //         'expire'   => $ex,
            //         'qty'      => $userVip->qty + $qty,
            //         'total'    => $userVip->total + $total,
            //         'is_used'  => 0,

            //     ];
            //     $this->userVipRepository->update($data, $userVip->id);
            // } else {
            $data = [
                'type' => $type,
                'sender_id' => $sender_id,
                'user_id' => $user_id,
                'vip_id' => $vip->id,
                'level' => $vip->level,
                'expire' => $ex,
                'qty' => $qty,
                'price' => $vip->price,
                'total' => $total,
                'is_used' => 0,
            ];

            $data = $this->userVipRepository->create($data);
            // }
            Common::handelVip($vip, $user, null, $data);
            DB::commit();
            $ex = Carbon::parse($ex)->diffInDays(now());
            CustomNotification::vips($user, $ex, $vip->img);
            return Common::apiResponse(1, 'done', null, 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function authUserSend($request, $total)
    {
        $type = 1;
        if (!$request->to_user) throw new Exception(__('api_responses.missing_params'));
        $user_id = $request->to_user;
        $user = $this->userRepository->searchUser($user_id);
        if (!$user) throw new Exception(__('user not found'));
        $user_id = $user->id;
        $sender = $request->user();
        $sender_id = $sender->id;
        if ($sender->di < $total) throw new Exception(__('api_responses.low_balance'));
        $from = $sender;
        return [$user_id, $from, $type, $sender_id, $user];
    }

    public function authUser($request, $total)
    {
        $type = 0;
        $user = $request->user();
        $user_id = $user->id;
        $sender_id = 0;
        if ($user->di < $total) throw new Exception(__('api_responses.low_balance'));
        $from = $user;
        return [$user_id, $from, $type, $sender_id, $user];
    }

    public function vipUserList($userId)
    {
        // $userVip = $this->userVipRepository->getAllByUserId($userId);
        // $vipPrivileges = $this->vipPrivilegeRepository->all();
        // $oVips =  $userVip->pluck('OVip');
        // $wares = $this->wareRepository->getOVip($oVips->pluck('level'), $vipPrivileges->pluck('type'));
        // $oVips = $oVips->map(function ($oVip) use ($wares) {
        //     $filteredWares = $wares->where('level', $oVip->level);
        //     $oVip->setRelation('wares', $filteredWares);
        //     return $oVip;
        // });

        $userVips = $this->userVipRepository->getAllByUserId($userId);
        $vipPrivileges = $this->vipPrivilegeRepository->all();

        $oVips = $userVips->pluck('OVip')->filter();

        $wares = $this->wareRepository->getOVip(
            $oVips->pluck('level')->unique(),
            $vipPrivileges->pluck('type')->unique()
        );
        $userVips->each(function ($userVip) use ($wares) {
            $oVip = $userVip->OVip;
            if ($oVip) {
                $filteredWares = $wares->where('level', $oVip->level);
                $oVip->setRelation('wares', $filteredWares);
            }
        });

        return [
            'all_privileges' => $vipPrivileges,
            'o_vips' => $userVips,
        ];
    }
}
