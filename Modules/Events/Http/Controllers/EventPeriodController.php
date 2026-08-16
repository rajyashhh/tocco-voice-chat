<?php

namespace Modules\Events\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\GiftLog;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\WeeklyStar;
use Modules\Events\Entities\Winner;
use Modules\Events\Transformers\TopWeeklyStarUsersResource;
use Modules\Events\Transformers\UserWeeklyStar;
use Modules\Events\Transformers\WeeklyEventResource;
use Modules\Events\Transformers\WeeklyStarGift;


class EventPeriodController extends Controller
{
    public function topUsersEvent(Request $request)
    {
        $weeklyEvent =
            WeeklyStar::whereType('event_period')->currentEvent()->first();

        if (!$weeklyEvent) return Common::apiResponse(0, __('there is event now'), null, 422);
        $giftIds             = $weeklyEvent->gifts->pluck('id')->toArray();
        $authenticatedUserId = Auth::user();
        $data                =
            GiftLog::whereIn('giftId', $giftIds)->with('sender')->select(DB::raw('sum(giftPrice) as totalGiftNum'), 'sender_id')
                   ->groupBy('sender_id')->whereBetween('created_at', [
                    $weeklyEvent->start_date, $weeklyEvent->end_date
                ])->orWhere(fn($q) => $q->where('sender_id', $authenticatedUserId->id)->whereBetween('created_at', [
                    $weeklyEvent->start_date, $weeklyEvent->end_date
                ]))
                   ->orderByDesc('totalGiftNum')->get();
        $firstTenQueries     = $data->take(10);
        $existsInArray       = $firstTenQueries->contains('sender_id', $authenticatedUserId->id);
        $data                = [
            'top'  => TopWeeklyStarUsersResource::collection($firstTenQueries),
            'user' => $existsInArray == true ? null : new UserWeeklyStar($authenticatedUserId, $data->where('sender_id', $request->user()->id)->first()),
        ];
        return Common::apiResponse(1, '', $data);
    }



    public function roleEvent()
    {
        $weeklyEvent = WeeklyStar::whereType('event_period')->currentEvent()->first();
        if (!$weeklyEvent) return Common::apiResponse(0, __('there is weekly star now'), null, 422);
        $previousWeeklyEvent = WeeklyStar::previousEvent()
                                         ->with('gifts')
                                         ->orderBy('start_date', 'desc')
                                         ->first();

        if ($previousWeeklyEvent) {
            $winners = Winner::where('weekly_star_id', $previousWeeklyEvent->id)->with('user', 'weeklyEvent')->get();
        }

        if(!isset($winners) || $winners->count() == 0){
            $winners = collect(array_fill(0, 3, []));
        }

        $data = [
//            'winner_previous_event' => TopPreviousResource::collection($winners),
            'details' => new WeeklyEventResource($weeklyEvent,'event_period'),
//            'Next_event_gifts' => $nextWeeklyEvent != null ? weeklyGiftResource::collection($nextWeeklyEvent?->gifts) : null,
        ];
        return Common::apiResponse(1, '', $data);
    }


    public function topDetails()
    {
        $weeklyEvent = WeeklyStar::with(['rewards'])
                                 ->whereType("event_period")
                                 ->currentEvent()
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
}
