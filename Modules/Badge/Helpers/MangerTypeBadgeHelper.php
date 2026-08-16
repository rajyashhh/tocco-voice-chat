<?php

namespace Modules\Badge\Helpers;

use App\Models\User;
use App\Models\MangerType;
use App\Helpers\UserCommon;
use Modules\Badge\Entities\Badge;
use Modules\Badge\Entities\UserBadge;

class MangerTypeBadgeHelper
{
    /**
     * receive_type namespace owned exclusively by position (manger_type) badges.
     * Never collides with role_rewards ("Role:{id}") or any other source, so
     * revoke only ever touches rows this helper created.
     */
    public static function receiveType(int $mangerTypeId): string
    {
        return "MangerType:{$mangerTypeId}";
    }

    /**
     * Materialize the badges of a position onto a single user.
     *
     * Idempotent per source: if the user already owns the badge under this
     * position's receive_type it is skipped. Cross-source safe: if the badge is
     * already active on the user from another source (e.g. role_rewards) we do
     * not create a duplicate row.
     */
    public static function giveBadges(User $user, int $mangerTypeId): void
    {
        $mangerType = MangerType::find($mangerTypeId);
        if (! $mangerType) {
            return;
        }

        $receiveType = self::receiveType($mangerTypeId);

        foreach ($mangerType->badges as $badge) {
            $ownsFromThisPosition = UserBadge::where('user_id', $user->id)
                ->where('badge_id', $badge->id)
                ->where('receive_type', $receiveType)
                ->exists();

            if ($ownsFromThisPosition) {
                continue;
            }

            $activeFromAnotherSource = UserBadge::where('user_id', $user->id)
                ->where('badge_id', $badge->id)
                ->where('receive_type', '!=', $receiveType)
                ->active()
                ->exists();

            if ($activeFromAnotherSource) {
                continue;
            }

            $expireDays = (int) ($badge->pivot->expire ?? 0);

            UserBadge::create([
                'user_id'      => $user->id,
                'badge_id'     => $badge->id,
                'expire'       => $expireDays === 0 ? 0 : time() + ($expireDays * 86400),
                'receive_type' => $receiveType,
            ]);
        }
    }

    /**
     * Remove only the badges this position materialized onto the user.
     * Scoped by receive_type, so role_rewards / manual / other sources are untouched.
     */
    public static function revokeBadges(User $user, int $mangerTypeId): void
    {
        UserCommon::removeBadgeFromUserByReceiverType(
            $user,
            null,
            self::receiveType($mangerTypeId)
        );
    }

    /**
     * Reconcile a single user after their manger_type_id changed.
     */
    public static function syncUser(User $user, ?int $oldMangerTypeId, ?int $newMangerTypeId): void
    {
        if ($oldMangerTypeId === $newMangerTypeId) {
            return;
        }

        if ($oldMangerTypeId) {
            self::revokeBadges($user, $oldMangerTypeId);
        }

        if ($newMangerTypeId) {
            self::giveBadges($user, $newMangerTypeId);
        }
    }

    /**
     * Reconcile every user holding a position after its badge links changed
     * (link added or removed). Revoke-then-give per user makes it converge to the
     * current linkage regardless of previous state.
     */
    public static function resyncUser(User $user, int $mangerTypeId): void
    {
        self::revokeBadges($user, $mangerTypeId);
        self::giveBadges($user, $mangerTypeId);
    }
}
