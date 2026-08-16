<?php

namespace Modules\Events\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\GiftLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\WeeklyStar;
use Modules\Events\Entities\Winner;
use Modules\Events\Transformers\TopPreviousResource;
use Modules\Events\Transformers\TopWeeklyStarUsersResource;
use Modules\Events\Transformers\UserWeeklyStar;
use Modules\Events\Transformers\WeeklyEventResource;
use Modules\Events\Transformers\weeklyGiftResource;
use Modules\Events\Transformers\WeeklyStarGift;


class WeeklyStarController extends Controller
{
    public function previousWeeklyEvent(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        $weeklyEvent = WeeklyStar::previousEvent()->weeklyStar()->latest()->first();
        if (!$weeklyEvent) {
            return Common::apiResponse(0, __('no weekly star'), null, 422);
        }

        $giftIds = $weeklyEvent->gifts->pluck('id')->toArray();

        $top10 = Cache::remember(
            "weekly_star_prev_top10_{$weeklyEvent->id}",
            60,
            fn () => $this->topSenders($giftIds, $weeklyEvent->start_date, $weeklyEvent->end_date)
        );

        if ($top10->isEmpty()) {
            return Common::apiResponse(1, '', [
                'top' => [],
                'user' => new UserWeeklyStar($user, null),
            ]);
        }

        $userData = $top10->contains('sender_id', $userId)
            ? (object)[]
            : new UserWeeklyStar($user, $this->senderTotals($giftIds, $weeklyEvent->start_date, $weeklyEvent->end_date, $userId));

        return Common::apiResponse(1, '', [
            'top'  => TopWeeklyStarUsersResource::collection($top10),
            'user' => $userData,
        ]);
    }


    public function topUsersEvent(Request $request)
    {
        $weeklyEvent = WeeklyStar::currentEvent()
            ->weeklyStar()
            ->orderByDesc('start_date')
            ->first();

        if (!$weeklyEvent) return Common::apiResponse(0, __('there is weekly star now'), null, 422);

        $giftIds = $weeklyEvent->gifts->pluck('id')->toArray();
        $authenticatedUser = Auth::user();

        $top10 = Cache::remember(
            "weekly_star_top10_{$weeklyEvent->id}",
            60,
            fn () => $this->topSenders($giftIds, $weeklyEvent->start_date, $weeklyEvent->end_date)
        );

        $authenticatedUserGift = $this->senderTotals($giftIds, $weeklyEvent->start_date, $weeklyEvent->end_date, $authenticatedUser->id);

        $data = [
            'top'  => TopWeeklyStarUsersResource::collection($top10),
            'user' => new UserWeeklyStar($authenticatedUser, $authenticatedUserGift),
        ];
        return Common::apiResponse(1, '', $data);
    }



    public function roleEvent()
    {
        $weeklyEvent = WeeklyStar::currentEvent()->weeklyStar()
            ->orderByDesc('start_date')
            ->first();
        if (!$weeklyEvent) return Common::apiResponse(0, __('there is weekly star now'), null, 422);
        $previousWeeklyEvent = WeeklyStar::previousEvent()->weeklyStar()
                                         ->with('gifts')
                                         ->orderBy('start_date', 'desc')
                                         ->first();

        if ($previousWeeklyEvent) {
            $winners = Winner::where('weekly_star_id', $previousWeeklyEvent->id)->with('user', 'weeklyEvent')->get();
        }

        if(!isset($winners) || $winners->count() == 0){
            $winners = collect(array_fill(0, 3, []));
        }
        $nextWeeklyEvent = WeeklyStar::where('start_date', '>', $weeklyEvent->start_date)
                                     ->with('gifts')
                                     ->orderBy('start_date', 'desc')
                                     ->first();

        $previousWeeklyEvent = WeeklyStar::where('start_date', '<', $weeklyEvent->start_date)
        ->with('gifts')
        ->orderBy('start_date', 'desc')
        ->first();

        $data = [
            'winner_previous_event' => TopPreviousResource::collection($winners),
            'weekly_event' => new WeeklyEventResource($weeklyEvent,'weekly_star'),
            'Next_event_gifts' => $nextWeeklyEvent != null ? weeklyGiftResource::collection($nextWeeklyEvent?->gifts) : null,
            'Previous_event_gifts' => $previousWeeklyEvent != null ? weeklyGiftResource::collection($previousWeeklyEvent?->gifts) : null,
        ];
        return Common::apiResponse(1, '', $data);
    }


    public function topDetails()
    {
        $weeklyEvent = WeeklyStar::currentEvent()->weeklyStar()->with(['rewards'])
            ->orderByDesc('start_date')
            ->first();

        if (!$weeklyEvent) {
            return Common::apiResponse(0, __('there is no weekly star now'), null, 422);
        }

        $rewards = collect($weeklyEvent->rewards);

        $data = [
            'top_1' => WeeklyStarGift::collection($rewards->where("level", 1)),
            'top_2' => WeeklyStarGift::collection($rewards->where("level", 2)),
            'top_3' => WeeklyStarGift::collection($rewards->where("level", 3)),
        ];

        return Common::apiResponse(1, '', $data);
    }

    private function topSenders(array $giftIds, $startDate, $endDate)
    {
        if (empty($giftIds)) {
            return collect();
        }

        return GiftLog::whereIn('giftId', $giftIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('SUM(giftPrice) as totalGiftNum'), 'sender_id')
            ->with('sender')
            ->groupBy('sender_id')
            ->orderByDesc('totalGiftNum')
            ->limit(10)
            ->get();
    }

    private function senderTotals(array $giftIds, $startDate, $endDate, int $userId)
    {
        if (empty($giftIds)) {
            return null;
        }

        return GiftLog::whereIn('giftId', $giftIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('sender_id', $userId)
            ->select(DB::raw('SUM(giftPrice) as totalGiftNum'), 'sender_id')
            ->groupBy('sender_id')
            ->first();
    }
}