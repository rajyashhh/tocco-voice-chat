<?php

namespace App\Http\Controllers\Web;

use App\Classes\PaymentGateways\Stripe;
use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\CoinLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PaymentController extends Controller
{
    public function success(Request $request)
    {

        if ($request->p_method == 'strip') {

            $coinLog = CoinLog::query()->where('trx', $request->trx)->where('method', 'strip')->first();
            if (!$coinLog) return Common::apiResponse(0, 'cannot find transaction', null, 404);

            $session_id = $coinLog->pid;
            $secret = Setting::where('key', 'stripe_test_secret_key')->first();
            if (Stripe::status($session_id, $secret)->payment_status != "paid") {
                return Common::apiResponse(0, 'fail', null, 400);
            }

            // Claim + credit atomically via the morphic owner (user or shipping
            // agency), so an agency-owned log credits the agency's balance rather
            // than a user's di. The coin_log row is locked and its status re-read
            // under the lock, so two deliveries of this success redirect (browser
            // retry / double request) cannot both pass the "not paid" check and
            // double-credit. Mirrors CoinHelper::applyCoinLog.
            $credited = DB::transaction(function () use ($coinLog) {
                $locked = CoinLog::whereKey($coinLog->getKey())->lockForUpdate()->first();

                if (!$locked || (int) $locked->status === 1) {
                    return false;
                }

                $owner = $locked->owner;

                if ($owner instanceof User) {
                    $owner = User::whereKey($owner->getKey())->lockForUpdate()->first();
                    if (!$owner) {
                        return null;
                    }
                    $owner->increment('di', $locked->obtained_coins);
                } elseif ($owner instanceof \App\Models\ShippingAgency) {
                    $owner = \App\Models\ShippingAgency::whereKey($owner->getKey())->lockForUpdate()->first();
                    if (!$owner) {
                        return null;
                    }
                    $owner->increment('coins', $locked->obtained_coins);
                } else {
                    return null;
                }

                $locked->update(['status' => 1]);

                return true;
            });

            if ($credited === null) {
                return Common::apiResponse(0, 'paid but cant found user', null, 404);
            }

            return Common::apiResponse(1, 'successfully paid', null, 200);
        }
        return Common::apiResponse(0, 'fail', null, 400);
    }

    public function fail()
    {
        return Common::apiResponse(0, 'fail', null, 400);
    }

}
