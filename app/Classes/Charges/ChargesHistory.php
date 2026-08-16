<?php

namespace App\Classes\Charges;

use App\Exceptions\NotInfCoins;
use App\Helpers\Common;
use App\Jobs\AllOpeningRoomsZegoRequest;
use App\Models\Charge;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\Room\RoomRepoInterface;
use Illuminate\Validation\ValidationException;

class ChargesHistory
{

    public function charge_make_history($user_id,$value_before,$value_after)
    {
        $amount=($value_after - $value_before);

        $userCoins = \Cache::rememberForever('user_coins', function () {
            $setting = Setting::where('key', 'user_coins')->first();
            return $setting?->value ?? 1;
        });
        $usdAmount = $userCoins > 0 ? $amount / $userCoins : 0;

        $data=Charge::create([
            "charger_id"=>auth()->user()->id,
            "charger_type"=>"dash",
            "user_id"=>$user_id,
            "user_type"=>'user',
            "amount"=>$amount,
            "usd"=>$usdAmount,
            "amount_type"=>2,
            "balance_before"=>$value_before,
        ]);
    }
}
