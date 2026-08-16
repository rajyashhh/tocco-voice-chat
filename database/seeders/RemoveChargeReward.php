<?php

namespace Database\Seeders;

use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use App\Models\Ware;
use Modules\Vip\Entities\UserVip;
use App\Models\RoomGame;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\RewardTarget;
use Modules\Events\Entities\UserChargeEvent;
use Modules\Events\Entities\ChargeTargetEvent;
use Modules\Achievement\Entities\UserAchievementLevel;



class RemoveChargeReward extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $winnerCharges = UserChargeEvent::with('event', 'user')->whereNotNull("user_id")->get();

        foreach ($winnerCharges as $winnerCharge) {
            $user = User::query()
                ->withSum('charges', 'amount')
                ->withSum('coinLogs', 'obtained_coins')
                ->find($winnerCharge->user->id);

            if (!$user) {
                continue;
            }

            $chargesSum = $user->charges_sum_amount ?? 0;
            $coinSum = $user->coin_logs_sum_obtained_coins ?? 0;
            $total = $chargesSum + $coinSum;

            $target = ChargeTargetEvent::find($winnerCharge->charge_event_id);
            //dd(123,$target,$total);
            if (!$target) {
                continue;
            }

            // Check if user doesn't meet the required total OR is type_user == 3
            if (($total < $target->value) || ($user->type_user == 3)) {

                $rewards = RewardTarget::where('charge_event_id', $target->id)->get();

                foreach ($rewards as $reward) {
                    if ($reward->type === 'vip') {
                        $vip = OVip::find($reward->target);

                        if ($vip) {
                            $userVip = UserVip::where([
                                'user_id' => $user->id,
                                'vip_id' => $vip->id,
                            ])->first();

                            if ($userVip) {
                                Pack::where([
                                    'user_id' => $user->id,
                                    'vip_user_id' => $userVip->id,
                                ])->delete();

                                $userVip->delete();
                            }
                        }
                    } elseif ($reward->type === 'ware') {
                        $ware =  Ware::where('id', $reward->target)->exists();
                        if ($ware) {

                            Pack::where('user_id', $user->id)
                                ->where('target_id', $reward->target)
                                ->delete();
                        }
                    } elseif ($reward->type === 'achievement') {
                        UserAchievementLevel::where([
                            'user_id' => $user->id,
                            'custom_image' => $reward->target,
                        ])->delete();
                    }
                }
            }
        }
    }
}
