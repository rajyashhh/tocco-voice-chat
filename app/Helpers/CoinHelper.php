<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\ShippingAgency;
use App\Models\CoinLog;
use Illuminate\Support\Facades\DB;
use Modules\Public\Http\Services\UserCounterServices;

class CoinHelper
{
    /**
     * Claim + credit a pending coin_log atomically.
     *
     * The coin_log row is locked and its status re-read under the lock, so two
     * deliveries of the same gateway callback cannot both pass the "not paid"
     * check and double-credit the owner. Mirrors PaymentTrait::webhookPayment.
     *
     * Returns the credited owner, or false when there is nothing to credit
     * (already processed, no resolvable owner).
     *
     * @return User|ShippingAgency|false
     */
    public static function applyCoinLog(CoinLog $coinLog)
    {
        $result = DB::transaction(function () use ($coinLog) {
            $locked = CoinLog::whereKey($coinLog->getKey())->lockForUpdate()->first();

            if (!$locked || (int) $locked->status === 1) {
                return false;
            }

            $owner = $locked->owner;

            if ($owner instanceof User) {
                $owner = User::whereKey($owner->getKey())->lockForUpdate()->first();
                if (!$owner) {
                    return false;
                }
                $owner->increment('di', $locked->obtained_coins);
            } elseif ($owner instanceof ShippingAgency) {
                $owner = ShippingAgency::whereKey($owner->getKey())->lockForUpdate()->first();
                if (!$owner) {
                    return false;
                }
                $owner->increment('coins', $locked->obtained_coins);
            } else {
                return false;
            }

            $locked->update(['status' => 1]);

            return $owner;
        });

        if ($result instanceof User) {
            Common::sendOfficialMessage(@$result->id, __('congratulations'), __('your recharge success'));
            (new UserCounterServices)->eventUser($result, 'official-messages');
        }

        return $result;
    }
}
