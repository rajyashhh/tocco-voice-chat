<?php

namespace Modules\CP\Http\Services;

use App\Enums\UserCoinLogType;
use App\Facades\CustomNotification;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Helpers\UserCommon;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use Modules\Vip\Entities\Vip;
use App\Models\Ware;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\CP\Entities\Cp as EntitiesCp;
use Modules\CP\Entities\CpLevel;
use Modules\CP\Entities\CpLevelGift;
use Modules\CP\Entities\CpLevelTakeGift;
use Modules\Vip\Helpers\VipCommon;

class CpService
{
    public function processCpWhenSendGift(User $sender, $receivers, int $giftId, int $giftPrice)
    {
        $cpIds = [];
        if (count($receivers) > 0) {
            foreach ($receivers as $receiver) {
                $cpId = $this->processGiftForReceiver($sender, $receiver, $giftId, $giftPrice);
                if ($cpId) {
                    $cpIds[$receiver->id] = $cpId;
                }
            }
        }

        return $cpIds;
    }

    /**
     * @throws \Throwable
     */
    protected function processGiftForReceiver(User $sender, User $receiver, int $giftId, int $giftPrice)
    {
        $checkIfExistCp = EntitiesCp::where(function ($query) use ($sender, $receiver) {
            $query->where('user_one_id', $sender->id)
                ->where('user_two_id', $receiver->id)
                ->orWhere(function ($query) use ($sender, $receiver) {
                    $query->where('user_two_id', $sender->id)
                        ->where('user_one_id', $receiver->id);
                });
        })
            ->whereIn('status', [1, 4])
            ->whereHas('cpRelation', function ($q) {
                $q->where('type', '!=', 'solution');
            })
            ->first();

        if (!$checkIfExistCp) {
            return false;
        }

        $this->upgradeLevelAndExp($checkIfExistCp, $giftPrice);
        return $checkIfExistCp->id;
    }

    /**
     * @throws \Throwable
     */
    public function upgradeLevelAndExp(?object $cp, $diamonds = null): bool
    {
        if (!$cp) return false;

        $newDi = $cp->di + $diamonds;
        $levels = $this->getEligibleLevels(
            $cp->cp_relation_id,
            $newDi,
            $cp->level_id
        );

        if (!$levels->isEmpty()) {
            foreach ($levels as $level){
                DB::table('cps')->where('id', $cp->id)->update([
                    'di' => $newDi,
                    'level_id' => $level->id
                ]);
                if ($level->level) $this->assignGifts($level, $cp);
            }
        } else {
            DB::table('cps')->where('id', $cp->id)->update([
                'di' => $newDi
            ]);
        }

        return true;
    }

    public function getEligibleLevels(int $cpRelationId, int $totalCoins, int $currentLevelId)
    {
        return CpLevel::query()
            ->where('cp_relation_id', $cpRelationId)
            ->where('id', '>', $currentLevelId)
            ->where('exp', '<=', $totalCoins)
            ->orderBy('exp')
            ->get();
    }

    public function getLevel(int $cpRelationId, int $totalCoins)
    {
        return CpLevel::query()->where('cp_relation_id', $cpRelationId)->where('exp', '<=', $totalCoins)->orderByDesc('exp')->limit(1)->first();
    }

    public function getLevels(int $cpRelationId, int $totalCoins)
    {
        return CpLevel::query()->where('cp_relation_id', $cpRelationId)->where('exp', '<=', $totalCoins)->get();
    }

    //////////////////////////////////////////// assign gift ///////////////////////////////////////////////////////////////////

    /**
     * @throws \Throwable
     */
    protected function assignGifts($level, $cp)
    {
        // Check if the CP has already taken the gift for the level
        if ($this->hasTakenGift($cp->id, $level->level)) {
            return true;
        }

        // Fetch rewards for the specified level
        $rewards = $this->getRewardsForLevel($level->id);
        if (!$rewards) return true;
        // Define users associated with the CP
        $userOne = $this->getUserById($cp->user_one_id);
        $userTwo = $this->getUserById($cp->user_two_id);

        // Distribute rewards to the users
        $this->distributeRewards($rewards, $userOne, $userTwo);

        // Mark the gift as taken for the CP and level
        $this->markGiftAsTaken($cp->id, $level->level);
    }

    protected function getUserById($id)
    {
        return User::find($id);
    }

    protected function hasTakenGift($cpId, $level)
    {
        return CpLevelTakeGift::where(['cp_id' => $cpId, 'level' => $level])->exists();
    }

    protected function getRewardsForLevel($level)
    {
        return CpLevelGift::whereHas('cp_level', function ($q) use ($level) {
            $q->where('id', $level);
        })->get();
    }

    /**
     * @throws \Throwable
     */
    protected function distributeRewards($rewards, $userOne, $userTwo)
    {
        foreach ($rewards as $reward) {
            switch ($reward->type) {
                case 'coins':
                    $this->assignCoins($reward, $userOne, $userTwo);
                    break;
                case 'vip':
                    $this->assignVip($reward, $reward->expire, $userOne, $userTwo);
                    break;
                case 'ware':
                    $ware = Ware::find($reward->item_id);
                    if ($ware) $this->assignWare($ware, $reward, $userOne, $userTwo);
                    break;
                case 'achievement':
                    $this->assignAchievement($reward, $reward->expire, $userOne, $userTwo);
                    break;
            }
        }
    }

    protected function assignCoins($reward, $userOne, $userTwo): void
    {
        [$userOneGender, $userTwoGender, $rewardGender] = $this->getGenders($userOne, $userTwo, $reward);

        $amount = $reward->item_id;
        $title = __('Coin Reward');
        $body = __('You have received :coin coin.', ['coin' => $amount]);

        if ($amount) {
            if ($rewardGender == $userOneGender || $rewardGender == 'all'){
                self::UserCoinLog($userOne,$userOne->di ,$amount);
                $userOne->increment('di', $amount);
                Common::sendOfficialMessage($userOne->id, $title, $body);
                $tokens_notfacion[] = DB::table('users')->where('id', $userOne->id)->value('notification_id');
                Common::send_firebase_notification($tokens_notfacion, $title, $body);
//                CustomNotification::charges($userOne, $title, $body, ['coin' => $amount]);
            }
            if ($rewardGender == $userTwoGender || $rewardGender == 'all') {

                self::UserCoinLog($userTwo,$userTwo->di ,$amount);

                $userTwo->increment('di', $amount);
                Common::sendOfficialMessage($userOne->id, $title, $body);
                $tokens_notfacion[] = DB::table('users')->where('id', $userOne->id)->value('notification_id');
                Common::send_firebase_notification($tokens_notfacion, $title, $body);
//                CustomNotification::charges($userTwo, $title, $body, ['coin' => $amount]);
            }
        }
    }

    protected function UserCoinLog($user,$amountBefore , $amount){

            UserCoinLogHelper::logByType(
                $user->id,
                $amount,
                $amountBefore,
                UserCoinLogType::CP,
                'cps'
            );
    }
    protected function assignVip($reward, $expire, $userOne, $userTwo)
    {
        [$userOneGender, $userTwoGender, $rewardGender] = $this->getGenders($userOne, $userTwo, $reward);

        $vipId = $reward->item_id;
        $vip = OVip::find($vipId);
        if ($vip) {
            if ($rewardGender == $userOneGender || $rewardGender == 'all'){
                VipCommon::createUserVip($vip,$userOne,  $expire,0,'',1,0,0,'cp-gifts');
            }
            if ($rewardGender == $userTwoGender || $rewardGender == 'all') {
                VipCommon::createUserVip( $vip,$userTwo, $expire,0,'',1,0,0,'cp-gifts');
            }
        }
    }

    protected function assignWare($ware, $reward, $userOne, $userTwo)
    {
        [$userOneGender, $userTwoGender, $rewardGender] = $this->getGenders($userOne, $userTwo, $reward);

        if ($rewardGender == $userOneGender || $rewardGender == 'all') {
            UserCommon::addEvintsWareToUser($userOne, $ware, $reward->expire,null,'cp-gift');
        }
        if ($rewardGender == $userTwoGender || $rewardGender == 'all') {
            UserCommon::addEvintsWareToUser($userTwo, $ware, $reward->expire,null,'cp-gift');
        }
    }

    protected function assignAchievement($reward, $expire, $userOne, $userTwo)
    {
        [$userOneGender, $userTwoGender, $rewardGender] = $this->getGenders($userOne, $userTwo, $reward);

        $itemId = $reward->item_id;

        // Handle different formats of $expire
        if (is_numeric($expire)) {
            // Assume it's a timestamp (seconds or milliseconds)
            if ($expire > 9999999999) {
                // Milliseconds -> convert to seconds
                $expire = (int) ($expire / 1000);
            }
            $dateTimestamp = Carbon::createFromTimestamp($expire)->format('Y-m-d H:i:s');
        } elseif (strtotime($expire)) {
            // It's a valid date string
            $dateTimestamp = Carbon::parse($expire)->format('Y-m-d H:i:s');
        } else {
            // Invalid date format fallback
            throw new \InvalidArgumentException("Invalid expire value: $expire");
        }

        $attributes = [
            'custom_image' => $itemId,
            'end_at'       => $dateTimestamp,
        ];

        $title = __('Achievement Reward');
        $body = __('You have received a new achievement.');

        if ($rewardGender == $userOneGender || $rewardGender == 'all'){
            UserAchievementLevel::create(array_merge($attributes, ['user_id' => $userOne->id]));
            Common::sendOfficialMessage($userOne->id, $title, $body);
            $tokens_notfacion[] = DB::table('users')->where('id', $userOne->id)->value('notification_id');
            Common::send_firebase_notification($tokens_notfacion, $title, $body);
//            CustomNotification::charges($userOne, $title, $body);
        }
        if ($rewardGender == $userTwoGender || $rewardGender == 'all') {
            UserAchievementLevel::create(array_merge($attributes, ['user_id' => $userTwo->id]));
            Common::sendOfficialMessage($userTwo->id, $title, $body);
            $tokens_notfacion[] = DB::table('users')->where('id', $userTwo->id)->value('notification_id');
            Common::send_firebase_notification($tokens_notfacion, $title, $body);
//            CustomNotification::charges($userTwo, $title, $body);
        }
    }

    public function getGenders($userOne, $userTwo, $reward): array
    {
        $userOneGender = $userOne->profile->gender == 1 ? 'male' : 'female';
        $userTwoGender = $userTwo->profile->gender == 1 ? 'male' : 'female';
        $rewardGender = $reward->gender;

        return [$userOneGender, $userTwoGender, $rewardGender];
    }

    protected function markGiftAsTaken($cpId, $level)
    {
        CpLevelTakeGift::create([
            'cp_id' => $cpId,
            'level' => $level,
        ]);
    }



}
