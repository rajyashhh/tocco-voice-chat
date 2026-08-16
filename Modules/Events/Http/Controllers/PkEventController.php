<?php

namespace Modules\Events\Http\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\GiftLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkWinner;
use Modules\Events\Transformers\PkEventResource;
use Modules\Events\Transformers\PkEventTopResource;
use Modules\Events\Transformers\PkGiftResource;
use Modules\Events\Transformers\UserWeeklyStar;

class PkEventController extends Controller
{
    public function topUsersPKEvent(Request $request)
    {
        $pkEvent = PkEvent::currentEvent()->first();

        if (!$pkEvent) {
            return Common::apiResponse(0, __('there is no event now'), null, 422);
        }

        $user = $request->user();
        $userId = $user->id;
        $type = (int) $request->input('type', 1);

        // Determine column and relation based on type
        $typeMap = [
            2 => ['column' => 'sender_id', 'relation' => 'sender'],
            3 => ['column' => 'roomowner_id', 'relation' => 'roomOwner'],
            1 => ['column' => 'receiver_id', 'relation' => 'receiver'],
        ];

        $columnInfo = $typeMap[$type] ?? $typeMap[1];
        $groupColumn = $columnInfo['column'];
        $relation = $columnInfo['relation'];

        $topEntries = Cache::remember(
            "pk_event_top:{$pkEvent->id}:{$type}",
            60,
            function () use ($pkEvent, $groupColumn, $relation, $type) {
                $query = GiftLog::query()
                    ->selectRaw("SUM(giftPrice) as totalGiftNum, {$groupColumn}")
                    ->where('pk', 1)
                    ->whereBetween('created_at', [$pkEvent->start_date, $pkEvent->end_date])
                    ->groupBy($groupColumn)
                    ->orderByDesc('totalGiftNum');

                if ($type === 3) {
                    $query->where('roomowner_id', '!=', 0)
                        ->with([
                            'roomOwner' => function ($q) {
                                $q->select('id', 'name', 'uuid')
                                    ->with(['profile', 'ownerRoom:id,uid,room_name,room_cover']);
                            },
                        ]);
                } else {
                    $query->with([$relation => function ($q) {
                        $q->select('id', 'name', 'uuid')->with('profile');
                    }]);
                }

                return $query->get();
            }
        );

        // First 20 top entries
        $top20 = $topEntries->take(20);

        // Check if user exists in top list
        $userExists = $top20->pluck($groupColumn)->contains($userId);

        // Get user's data only if not already in top
        $userData = null;
        if (!$userExists) {
            $userData = $topEntries->firstWhere($groupColumn, $userId);
        }

        // Standardize relation to "user" for resource collection
        $top20->each(function ($item) use ($relation) {
            $item->setRelation('user', $item->getRelation($relation));
            if ($relation !== 'user') {
                $item->unsetRelation($relation);
            }
        });

        return Common::apiResponse(1, '', [
            'top' => PkEventTopResource::collection($top20),
            'user' => $userExists ? null : new UserWeeklyStar($user, $userData),
        ]);
    }


    public function topDetails()
    {
        $pkEvent = PkEvent::currentEvent()->with(['rewards'])->first();
        if (!$pkEvent) {
            return Common::apiResponse(0, __('there is no event now'), null, 422);
        }
        $rewards = collect($pkEvent->rewards);

        $data    = [
            'pk_star' => new PkGiftResource($rewards->where("pk_type", 'pk-star')),
            'pk_king' => new PkGiftResource($rewards->where("pk_type", 'pk-king')),
            'pk_room' => new PkGiftResource($rewards->where("pk_type", 'pk-room')),
        ];
        return Common::apiResponse(1, '', $data);
    }


    public function pkEvent()
    {
        $pkEvent = PkEvent::currentEvent()->first();

        if (!$pkEvent) return Common::apiResponse(0, __('there is no event now'), null, 422);

        $previousWinners = Cache::remember('pk_event_prev_winners', 60, function () {
            $prevEvent = PkEvent::previousEvent()->first();
            if (!$prevEvent) {
                return collect();
            }

            return PkWinner::with(['winner.profile', 'winner.ownerRoom:id,uid,room_name,room_cover'])
                ->where('pk_event_id', $prevEvent->id)
                ->where('level', 1)
                ->get()
                ->keyBy('pk_type');
        });

        $king = $previousWinners->get('pk-king');
        $room = $previousWinners->get('pk-room');

        $data = [
            'winner_previous_event' => [
                "PK_king" => $king?->winner?->profile?->avatar ?? '',
                "PK_star" => $previousWinners->get('pk-star')?->winner?->profile?->avatar ?? '',
                "Room_pk" => $room?->winner?->ownerRoom?->room_cover ?? '',
            ],
            'pk_event'              => new PkEventResource($pkEvent),
        ];
        return Common::apiResponse(1, '', $data);
    }
}