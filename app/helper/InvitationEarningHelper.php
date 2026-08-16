<?php

namespace App\helper;

use App\Models\UserEarnInvitation;

class InvitationEarningHelper
{
    /**
     *
     * @param int $parentId   - الداعي
     * @param int $userId     - المدعو
     * @param string $sourceType - نوع المصدر (first_join_reward_host, first_join_reward_invitee, charge_percentage)
     * @param float $amount   - مقدار الربح
     * @param float|null $userCharge - قيمة الشحنة (إن وجدت)
     * @param float|null $parentPercentage - نسبة الداعي من الشحنة
     * @param int|null $chargeId - معرف الشحنة
     * @param array|null $meta - بيانات إضافية
     * @return UserEarnInvitation
     */
    public static function addEarning(
        int $parentId,
        int $userId,
        string $sourceType,
        float $amount,
        ?float $userCharge = null,
        ?float $parentPercentage = null,
        ?int $chargeId = null,
        ?array $meta = null
    ): UserEarnInvitation {
        return UserEarnInvitation::create([
            'parent_id'         => $parentId,
            'user_id'           => $userId,
            'source_type'       => $sourceType,
            'amount'            => $amount,
            'user_charge'       => $userCharge,
            'parent_percentage' => $parentPercentage,
            'charge_id'         => $chargeId,
            'is_claimed'        => false,
            'meta'              => $meta ,
        ]);
    }
}
