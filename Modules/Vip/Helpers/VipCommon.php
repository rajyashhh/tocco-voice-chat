<?php

namespace Modules\Vip\Helpers;

use App\Helpers\Common;
use Illuminate\Support\Facades\DB;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use Modules\Vip\Entities\UserVip;
use App\Models\Ware;

class VipCommon
{

    public static function createUserVip(OVip $vip, User $user, int $expire = 0, $dashUserId = 0, $typeSend = '', $qty = 1, $senderId = 0, $total = 0, $receiveType = 'not-sending', $isUsed = null, $sendNotification = 1 , $vip_gift_message = null, $vip_img = null): bool
    {
        try {
            DB::transaction(function () use ($vip, $user, $expire, $dashUserId, $typeSend, $senderId, $qty, $total, $receiveType, $isUsed) {
                $vipp = UserVip::create([
                    'type'      => 1,
                    'sender_id' => $senderId,
                    'user_id'   => $user->id,
                    'vip_id'    => $vip->id,
                    'level'     => $vip->level,
                    'expire'    => null,
                    'days'      => $expire,
                    'qty'       => $qty,
                    'price'     => $vip?->price,
                    'total'     => $total,
                    'is_used'   => $isUsed ? 1 : 0,
                    'using'   => $isUsed ? 1 : 0,
                    'num_used'   => $isUsed ? 1 : 0,
                    'dash_user_id'   => $dashUserId ?? 0,
                    'type_send' => $typeSend,
                    'receive_type' => $receiveType

                ]);
            });

            if ($sendNotification) {
               
                $vip_gift_message = $vip_gift_message ?? __('vip_gift_message') ;
                $data['image'] = $vip_img ?? $vip->img;

                
                Common::sendOfficialMessage(
                    $user->id,
                    __('congratulations'),
                    $vip_gift_message
                );

                $tokens_notification = [
                    DB::table('users')->where('id', $user->id)->value('notification_id')
                ];

                Common::send_firebase_notification(
                    $tokens_notification,
                    config('app.name_ar'),
                  $vip_gift_message .' : '. $user->name,
                    '',
                    $data
                );
            }

            return true;
        } catch (\Throwable $e) {
            //  \Log::error($e->getMessage());
            return false;
        }
    }


    public static function handleVipActivation(UserVip $userVip): void
    {
        $userVip->loadMissing(['user', 'OVip.privilegs']);

        match ($userVip->using) {
            0 => self::handleInitialActivation($userVip),
            1 => self::handleReactivation($userVip),
            default => throw new \InvalidArgumentException('Invalid is_using value'),
        };
    }

    protected static function handleInitialActivation(UserVip $userVip): void
    {
        $user = $userVip->user;
        $vip  = $userVip->OVip;

        self::updateVipUsage($userVip);
        self::deactivateOtherUserVips($user, $userVip);
        self::deactivateOldUserPacks($userVip, $vip, $user);
        self::activateVipWares($vip);
        self::assignWaresToUser($vip, $userVip, $user);
        self::updateUserCurrentVip($user);
    }

    protected static function handleReactivation(UserVip $userVip): void
    {
        $user = $userVip->user;
        $vip  = $userVip->OVip;

        self::updateVipUsage($userVip);
        self::deactivateOtherUserVips($user, $userVip);
        self::deactivateOldUserPacks($userVip, $vip, $user);
        self::assignWaresToUser($vip, $userVip, $user);
        self::updateUserCurrentVip($user);
    }

    public static function deactivateVip(UserVip $vip): void
    {
        if ($vip) {
            $vip->update(['is_used' => 0]);
            $vip->packs()->update(['is_used' => 0]);
        }
    }
    private static function updateVipUsage(UserVip $userVip): void
    {
        $userVip->num_used++;
        $update = [
            'num_used' => $userVip->num_used,
            'is_used'  => 1,
            'using'    => 1,
        ];

        if ($userVip->using == 0 && $userVip->days > 0) {
            $update['expire'] = now()->addDays($userVip->days * $userVip->qty)->timestamp;
        }

        $userVip->update($update);
    }

    private static function deactivateOtherUserVips(User $user, UserVip $current): void
    {
        $vip = UserVip::where('user_id', $user->id)
            ->where('id', '!=', $current->id)
            ->where('is_used', 1)
            ->where(function ($q) {
                $q->where('expire', 0)
                    ->orWhere('expire', '>=', now()->timestamp);
            })->first();

        if ($vip) {
            self::deactivateVip($vip);
        }
    }

    private static function deactivateOldUserPacks(UserVip $userVip, OVip $vip, User $user): void
    {
        if (!$userVip->is_used) return;

        $types = $vip->privilegs->pluck('type')->filter()->unique()->toArray();

        Pack::where('get_type', 1)
            ->where('user_id', $user->id)
            ->whereIn('type', $types)
            ->where('vip_user_id', '!=', $userVip->id)
            ->update(['is_used' => 0]);
    }

    private static function activateVipWares(OVip $vip): void
    {
        foreach ($vip->privilegs->pluck('type')->toArray() as $type) {
            $ware = Ware::where('get_type', 1)
                ->where('level', $vip->level)
                ->where('type', $type)
                ->first();

            if ($ware) {
                $ware->update([
                    'is_active_for_vip' => 1,
                    'enable' => 1,
                ]);
            }
        }
    }

    private static function assignWaresToUser(OVip $vip, UserVip $userVip, User $user): void
    {
        $types = $vip->privilegs->pluck('type')->toArray();

        $expireTimestamp = $userVip->expire;
        if (!$expireTimestamp || $expireTimestamp < now()->timestamp) {
            $expireTimestamp = now()->addDays($userVip->days * $userVip->qty)->timestamp;
        }

        $wares = Ware::where('get_type', 1)
            ->where('enable', 1)
            ->where('level', $vip->level)
            ->whereIn('type', $types)
            ->where('is_active_for_vip', 1)
            ->get();

        foreach ($wares as $ware) {
            self::assignWareToUser($ware, $user, $userVip, $expireTimestamp);
        }
    }

    private static function updateUserCurrentVip(User $user): void
    {
        $activeVip = UserVip::where('user_id', $user->id)
            ->where('is_used', 1)
            ->where(function ($q) {
                $q->where('expire', 0)
                    ->orWhere('expire', '>=', now()->timestamp);
            })
            ->orderByDesc('level')
            ->first();

        if ($activeVip) {
            $user->update(['vip' => $activeVip->id]);
        }
    }

    private static function assignWareToUser($ware, $user, $userVip, $expireTimestamp)
    {
        Pack::query()
            ->where('user_id', $user->id)
            ->where('expire', '<', now()->timestamp)
            ->where('expire', '!=', 0)
            ->delete();

        $existingPack = Pack::query()
            ->where('user_id', $user->id)
            ->where('get_type', 1)
            ->where('target_id', $ware->id)
            ->where('vip_user_id', $userVip->id)
            ->where(function ($q) {
                $q->where('expire', '>=', now()->timestamp)
                    ->orWhere('expire', 0);
            })->first();



        if ($existingPack) {
            $existingPack->update(['is_used' => $userVip->is_used]);
        } else {
            Pack::create([
                'user_id'     => $user->id,
                'get_type'    => $ware->get_type,
                'type'        => $ware->type,
                'target_id'   => $ware->id,
                'num'         => 1,
                'expire'      => $expireTimestamp,
                'use_num'     => $ware->num,
                'vip_user_id' => $userVip->id,
                'is_used'     => $userVip->is_used,
                'using'       => 1,
                'receive_type' => $userVip->receive_type . '-' . $userVip->level

            ]);
        }

        if (in_array($ware->type, [4, 5, 6])) {
            self::userDress($ware, $user, $userVip->is_used);
            self::unUsePack([$ware->type], $user);
        }
    }




    public static function  unUsePack($type, $user)
    {
        Pack::where('type', $type)
            ->where('user_id', $user->id)
            ->where('get_type', '!=', 1)
            ->update(['is_used' => 0]);
    }

    public static function userDress($ware, $user, $isUsed)
    {
        $dressFieldMap = [
            4 => 'dress_1',
            5 => 'dress_2',
            6 => 'dress_3',
        ];

        if (isset($dressFieldMap[$ware->type])) {
            $field = $dressFieldMap[$ware->type];
            $user->$field = $isUsed ? $ware->id : null;
            $user->save();
        }
    }


    public static function  removeVipFromUser($user, $id, $receive_type)
    {
        $vip = UserVip::where('receive_type', $receive_type)
            ->where('user_id', $user->id)
            ->where('vip_id', $id)
            ->first();
        if (!$vip) {
            return;
        }


        $vipReceiveType = $receive_type . '-' . $vip->level;
        Pack::where('vip_user_id',  $vip->id)
            ->where('receive_type', $vipReceiveType)
            ->where('user_id', $user->id)
            ->delete();

        $vip->delete();
    }
}
