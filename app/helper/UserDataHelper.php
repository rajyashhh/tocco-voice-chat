<?php

namespace App\helper;

use App\Http\Resources\Api\V1\MiniUserResource;
use App\Http\Resources\Api\V1\NowRoomResource;
use App\Http\Resources\UserDataRoomResource;
use Carbon\Carbon;
use App\Models\User;

class UserDataHelper
{
    public static function formatAgency(User $user): ?array
    {
        if (!$user->agency) return null;

        $owner = $user->agency->app_owner_id == $user->id
            ? new \stdClass()
            : new MiniUserResource($user->agency->owner);

        return [
            'id'           => $user->agency->id,
            'name'         => $user->agency->name,
            'status'       => $user->agency->status,
            'image'        => $user->agency->img,
            'member_count' => $user->agency->members?->count() ?? 0,
            'owner'        => $owner,
        ];
    }

    public static function formatFamily(User $user): ?array
    {
        if (!$user->family) return null;

        return [
            'owner_id'       => $user->family->user_id,
            'family_name'    => $user->family->name,
            'img'            => $user->family->image,
            'num_of_members' => $user->family->members?->count() ?? 0,
        ];
    }

    /**
     * Single source of truth for a user's presence.
     *
     * Built from the live columns maintained by UpdateUserLastSeenJob and the
     * users:update-offline command (online + last_seen_at), and honours the
     * privacy toggle (last_active_hidden / pack type 20). When hidden, presence
     * is fully suppressed: online=0 and last_seen_at=null.
     *
     * @return array{online:int, last_seen_at:?string}
     */
    public static function presence(User $user, bool $respectPackExpiry = false): array
    {
        // V2 resources gate every privacy pack on getPackWithTypeV2 (which also
        // excludes EXPIRED packs); V1 uses getPackWithType (is_used only). Honor
        // the caller's version so the last-active toggle (pack 20) stays
        // consistent with the resource's sibling privacy fields — otherwise an
        // expired "hide last active" pack would keep suppressing V2 presence.
        $hidden = $respectPackExpiry
            ? $user->getPackWithTypeV2(20)
            : $user->getPackWithType(20);

        if ($hidden) {
            return ['online' => 0, 'last_seen_at' => null];
        }

        $lastSeen = $user->last_seen_at ? Carbon::parse($user->last_seen_at) : null;

        $isOnline = (int) ($user->online
            && $lastSeen
            && $lastSeen->greaterThanOrEqualTo(now()->subMinutes(15)));

        return [
            'online'       => $isOnline,
            'last_seen_at' => $lastSeen?->toIso8601String(),
        ];
    }

    /**
     * Human readable "last seen" string, derived from the unified presence
     * source (last_seen_at), not the legacy online_time column.
     */
    public static function formatOnlineTime(User $user): string
    {
        $presence = self::presence($user);

        if ($presence['online']) return __('online');

        if (!$presence['last_seen_at']) return '';

        $lastSeen = Carbon::parse($presence['last_seen_at']);

        return $lastSeen->isPast()
            ? $lastSeen->diffForHumans(now())
            : 'In the future';
    }

    public static function formatNowRoom(User $user)
    {
        if (!$user->now_room_uid) {
            return [];
        }
    
        if ($user->relationLoaded('nowRoomOwner')) {
            $nowRoomOwner = $user->nowRoomOwner;
        } else {
            $nowRoomOwner = $user->loadMissing([
                'nowRoomOwner.packs' => fn($q) => $q->where('is_used', 1)->with('ware'),
            ])->nowRoomOwner;
        }
    
        if ($nowRoomOwner?->getPackWithTypeV3(16)) {
            return (object)[];
        }
    
        return new NowRoomResource($user);
    }
    
    public static function formatShippingAgency(User $user): ?array
    {
        if (!$user->shippingAgency) return null;

        return [
            "id"                    => $user->shippingAgency->id,
            "name"                  => $user->shippingAgency->name ?? '',
            "image"                 => $user->shippingAgency->img ?? '',
            "complete-transactions" => $user->shippingAgency->charges?->count() ?? 0,
        ];
    }

    public static function getUserDress(User $user, $type, $dress, $item = 'img1'): string
    {
        $pack = $user->packs
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();

        return $pack?->ware?->{$item} ?? '';
    }
}
