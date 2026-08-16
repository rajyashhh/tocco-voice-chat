<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class UserPackHelper
{

    public static function getColorName(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 18)
            ->where('is_used',1)
            ->first()?->ware?->color ?? '';
    }

    public static function getFrameImage(?User $user) : string
    {
        if (!$user) return '';
        $ware = self::getFrameWare($user);
        return $ware?->img2 ?? ($ware?->img1 ?? '');
    }

    public static function getFrameId(?User $user) : string
    {
        if (!$user) return 0;
        $ware = self::getFrameWare($user);
        return $ware?->id ?? 0;
    }

    public static function getFrameType(?User $user) : string
    {
        if (!$user) return '';
        $ware = self::getFrameWare($user);
        return $ware?->image_type ?? '';
    }

    public static function getProfileFrameId(?User $user) : string
    {
        if (!$user) return 0;
        $ware = self::getProfileFrameWare($user);
        return $ware?->id ?? 0;
    }

    public static function getVipIcon(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 10)
            ->where('is_used',1)
            ->first()?->ware?->show_img ?? '';
    }

    public static function getVipImage(?User $user): string
    {
        if (!$user) return '';
        return $user->UserVip?->OVip?->img ?? '';
    }

    public static function getVipData(?User $user)
    {
        if (!$user) return null;
        return self::getPacks($user)
            ->where('type', 10)
            ->first();
    }

    public static function getGif(?User $user)
    {
        if (!$user) return null;
        return self::getPacks($user)
            ->where('type', 22)
            ->first();
    }

    public static function getIntroImage(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 6)
            ->first()?->ware?->show_img ?? '';
    }

    public static function getIntroFile(?User $user) : string
    {
        if (!$user) return '';
        $intro = self::getWare($user, 6);

        if (!$intro) {
            return '';
        }

        return !empty($intro->img1) && $intro->img1 !== 'NULL'
            ? $intro->img1
            : ($intro->img2 ?? '');
    }
    public static function getIntroId(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 6)
            ->first()?->ware?->id ?? '';
    }
    public static function getIntroType(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 6)
            ->where('is_used', true)
            ->first()?->ware?->image_type ?? '';
    }


    public static function getBubbleImage(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 5)
            ->where('is_used', true)
            ->first()?->ware?->show_img ?? '';
    }
    public static function getBubbleId(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 5)
            ->where('is_used', true)
            ->first()?->ware?->id ?? '';
    }


    public static function getWabbleId(?User $user) : string
    {
        if (!$user) return '';
        return self::getPacks($user)
            ->where('type', 12)
            ->where('is_used', true)
            ->first()?->ware?->id ?? '';
    }

    public static function hasAntBan(?User $user) : bool
    {
        if (!$user) return false;
        return self::hasPack($user, 15);
    }

    public static function hasHideOnlineTime(?User $user) : bool
    {
        if (!$user) return false;
        return self::hasPack($user, 20);
    }
    public static function hasHideCountry(?User $user) : bool
    {
        if (!$user) return false;
        return self::hasPack($user, 17);
    }


    /**
     * @param ?User $user
     * @return \App\Models\Ware|mixed|null
     */
    public static function getFrameWare(?User $user): mixed
    {
        if (!$user) return null;
        return self::getWare($user, 4);
    }

    public static function getProfileFrameWare(?User $user): mixed
    {
        if (!$user) return null;
        return self::getWare($user, 28);
    }

    /**
     * @param ?User $user
     * @return \Illuminate\Database\Eloquent\Collection|mixed
     */
    public static function getPacks(?User $user): mixed
    {
        if (!$user) return collect();
        return $user->relationLoaded('packs') ? $user->packs : $user->packs();
    }

    /**
     * @param ?User $user
     * @param int $type
     * @return null
     */
    public static function getWare(?User $user, int $type)
    {
        if (!$user) return null;
        return self::getPacks($user)
            ->where('type', $type)
            ->where('is_used', true)
            ->first()?->ware;
    }

    /**
     * @param ?User $user
     * @param int $type
     * @return bool
     */
    public static function hasPack(?User $user, int $type): bool
    {
        if (!$user) return false;
        return self::getPacks($user)
                ->where('type', $type)
                ->count() > 0;
    }


}
