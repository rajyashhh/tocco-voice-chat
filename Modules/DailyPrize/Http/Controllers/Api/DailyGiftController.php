<?php

namespace Modules\DailyPrize\Http\Controllers\Api;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use Carbon\Carbon;
use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Renderable;
use Modules\Achievement\Entities\Achievement;
use Modules\DailyPrize\Entities\DailyUserGift;
use Modules\DailyPrize\Entities\DailyGiftCount;
use Modules\Achievement\Entities\UserAchievement;
use Modules\DailyPrize\Transformers\WeeklyStarGift;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\DailyPrize\Http\Services\DailyPrizeService;
use Modules\Achievement\Http\Services\AchievementService;
use Modules\Achievement\Transformers\AchievementResource;
use Modules\Achievement\Transformers\AchievementDetailResource;
use Modules\Achievement\Transformers\AchievementOneLevelsResource;

class DailyGiftController extends Controller
{

    public function __construct(private DailyPrizeService $dailyPrizeService) {}

    public function current_day()
    {
        $user = Auth::user();

        //reset daily

        (new DailyPrizeService())->reset(Carbon::createFromTimestamp($user->real_online_time), $user->id);

        $currentDay = $this->getCurrentDay();


        $check_received = DailyGiftCount::query()
            ->where('user_id', $user->id)
            ->where("day_count", $currentDay)
            ->where('last_active', '>=', now()->subHours(24))
            ->first();
        $data = [
            'total_days'    => $currentDay,
            'current_day'   => ($currentDay % 7 == 0 ? 7 : $currentDay % 7),
            'gift'          => $this->getWeekGifts($currentDay),
            'is_received'   => $check_received != null ? true : false,
        ];
        return Common::apiResponse(1, '', $data);
    }

    public function getWeekGifts(int $currentDay): array
    {
        $DAYS_IN_WEEK = 7;
        $currentWeek = intdiv($currentDay - 1, $DAYS_IN_WEEK);

        $startDay = 1 + ($DAYS_IN_WEEK * $currentWeek);
        $endDay = $startDay + $DAYS_IN_WEEK - 1;

        $gifts = [];

        for ($day = $startDay; $day <= $endDay; $day++) {
            $gifts[] = [
                'day'  => $day,
                'gift' => $this->dailyPrizeService->getGift($day) ?? (object) [],
            ];
        }

        return $gifts;
    }
    public function receive_daily_prize()
    {
        $user = Auth::user();
        $currentDay = $this->getCurrentDay();

        $dailyGift = $this->dailyPrizeService->getDayGift($currentDay);
        if (!$dailyGift) {
            $currentDay = 1;
            $dailyGift = $this->dailyPrizeService->getDayGift($currentDay);
        }
        if (!$dailyGift) {
            return Common::apiResponse(0, '  لا يوجد هديه اليوم ', [], 400);
        }

        $result = DailyGiftCount::query()->where('user_id', $user->id)->orderByDesc('id')->first();
        if (!$this->dailyPrizeService->isNewDay($user->id) && $result != null) {
            return Common::apiResponse(0, __('It has not been 24 hours yet to receive the next gift.'), [], 400);
        }
        try {

            $type = $dailyGift->gift_type;
            $target = $dailyGift->target;
            $expire = $dailyGift->expire;

            $this->assignGiftToUser($type, $user, $target, $expire);
            DailyGiftCount::query()->updateOrCreate([
                'user_id' => $user->id,

            ], [
                'last_active' => now(),
                'day_count' => $currentDay,

            ]);
            DailyUserGift::create([
                'user_id'   => $user->id,
                'gift_type' => $type,
                'target'    => $target,
            ]);



            return Common::apiResponse(1, 'تم استلام الجائزه بنجاح', [], 200);
        } catch (\Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function getCurrentDay()
    {
        $user = Auth::user();
        $result = DailyGiftCount::query()->where('user_id', $user->id)->first();
        $currentDay = 1;
        if ($result != null) {
            $currentDay = $result->day_count;
            if ($this->dailyPrizeService->isNewDay($user->id)) {
                // Check if we've completed all days in the cycle
                $totalDays = $this->dailyPrizeService->dailyGiftsCount();
                if ($currentDay >= $totalDays) {
                    // Reset to day 1 to allow restarting from the beginning
                    $currentDay = 1;
                } else {
                    $currentDay += 1;
                }
            }
        }

        // لو مفيش هدية للـ day الحالي، نرجع للأول (day 1)
        if (!$this->dailyPrizeService->getDayGift($currentDay)) {
            $currentDay = 1;
        }

        return $currentDay;
    }
    public function assignGiftToUser(mixed $type,  $user, mixed $target, mixed $expire): void
    {

        if ($type == "coins") {

            $amountBefore =  Common::getCurrentBalance($user->id);
            UserCoinLogHelper::logByType(
                $user->id,
                $target,
                $amountBefore,
                UserCoinLogType::DAILY_GIFT,
            );

            $user->di += $target;
            $user->save();
        } elseif ($type == "vip") {
            $vip = OVip::query()->find($target);
            if ($vip) UserCommon::addVipToUser($user, $vip, $expire, null, 'daily-gift');
        } elseif ($type == "ware") {

            $ware = Ware::query()->find($target);

            if ($ware) UserCommon::addWareToUser($user, $ware, $expire, null, 'daily-gifts');
        } elseif ($type == "achievement") {
            $attributes = [
                'user_id'      => $user->id,
                'custom_achievement_id' => $target,
                'end_at' =>  Carbon::parse($expire)->format("Y-m-d H:i:s"),
                'receive_type' => 'daily-gifts',
            ];
            UserAchievementLevel::create($attributes);
        } elseif ($type == 'badge') {
            Common::userBadge($user->id, $target, $expire, 'daily-gifts');
        }
    }

    public function check_date_hours($date)
    {
        $specificDateTime = new \DateTime($date);
        $now = new \DateTime();
        $diff = $now->diff($specificDateTime);
        $hoursDifference = ($diff->days * 24) + $diff->h + ($diff->i / 60) + ($diff->s / 3600);
        if ($hoursDifference > 24) {
            return false;
        }
        return true;
    }
}
