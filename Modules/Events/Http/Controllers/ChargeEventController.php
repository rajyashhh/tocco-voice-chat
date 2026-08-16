<?php

namespace Modules\Events\Http\Controllers;

use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use Carbon\Carbon;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Events\Entities\ChargeKingWinner;
use Modules\Events\Entities\RewardTarget;
use Modules\Events\Entities\UserChargeEvent;
use Modules\Events\Entities\ChargeTargetEvent;
use Modules\Events\Repositories\ChargeKingRepository;
use Modules\Events\Transformers\TargetsResource;
use Modules\Events\Transformers\UserChargeResource;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Events\Transformers\TopUserChargeResource;

class ChargeEventController extends Controller
{
    public function __construct(private readonly ChargeKingRepository $chargeKingRepository) {}

    public function wonEvent()
    {
        $month = Carbon::now()->subMonth()->format('Y-m');

        $winner = ChargeKingWinner::where('month', $month)
            ->where('rank', 1)
            ->with('user.profile')
            ->first();

        if (!$winner || !$winner->user) {
            return Common::apiResponse(1, '', null);
        }

        return Common::apiResponse(1, '', new TopUserChargeResource($winner->user));
    }

    public function leaderboard()
    {
        $fromDate = now()->startOfMonth()->toDateString();
        $tillDate = now()->endOfMonth()->toDateString();

        $top = Cache::remember('charge_king_leaderboard_' . now()->format('Y-m'), 60, function () use ($fromDate, $tillDate) {
            return $this->chargeKingRepository->top($fromDate, $tillDate, 10)
                ->map(fn ($user, $index) => [
                    'rank' => $index + 1,
                    'user_id' => $user->id,
                    'uuid' => $user->uuid ?? 0,
                    'name' => $user->name ?? '',
                    'avatar' => $user->profile?->avatar ?? '',
                    'points' => (int) $user->total_sum,
                ])->values()->all();
        });

        $me = Auth::user();
        $myTotal = $this->chargeKingRepository->totalFor($me->id, $fromDate, $tillDate);
        $myRank = $this->chargeKingRepository->rankOf($myTotal, $fromDate, $tillDate);

        $now = Carbon::now();
        $diff = $now->diff($now->copy()->endOfMonth());

        return Common::apiResponse(1, '', [
            'top' => $top,
            'me' => [
                'user_id' => $me->id,
                'uuid' => $me->uuid ?? 0,
                'name' => $me->name ?? '',
                'avatar' => $me->profile?->avatar ?? '',
                'points' => $myTotal,
                'rank' => $myRank,
            ],
            'remainingTime' => [
                'day' => $diff->d,
                'hour' => $diff->h,
                'minute' => $diff->i,
                'second' => $diff->s,
            ],
        ]);
    }

    public function previousWinners()
    {
        $months = collect(range(1, 3))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->format('Y-m'));

        $winners = ChargeKingWinner::whereIn('month', $months)
            ->with('user.profile')
            ->orderByDesc('month')
            ->orderBy('rank')
            ->get()
            ->groupBy('month')
            ->map(fn ($rows, $month) => [
                'month' => $month,
                'winners' => $rows->map(fn ($row) => [
                    'rank' => (int) $row->rank,
                    'user_id' => $row->user_id,
                    'uuid' => $row->user?->uuid ?? 0,
                    'name' => $row->user?->name ?? '',
                    'avatar' => $row->user?->profile?->avatar ?? '',
                    'prize' => (int) $row->prize,
                ])->values(),
            ])->values();

        return Common::apiResponse(1, '', $winners);
    }

    public function chargeEventRole(Request $request)
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $user = Auth::user()->load([
            'charges' => fn($q) => $q->whereBetween('created_at', [$start, $end]),
            'coinLogs' => fn($q) => $q->whereBetween('created_at', [$start, $end])->where('status', 1),
        ]);

        return Common::apiResponse(1, '', new UserChargeResource($user));
    }

    public function targets()
    {
        $targets = ChargeTargetEvent::query()->with("rewards", 'users')->orderBy('value', 'asc')->get();
        $now = now();
        $user = User::withSum(['charges' => function ($query) use ($now) {
            $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
        }], 'amount')->withSum(['coinLogs' => function ($query) use ($now) {
            $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->where('status', 1);
        }], 'obtained_coins')->find(auth()->user()->id);
        return Common::apiResponse(1, '', new TargetsResource(
            $targets,
            $user->charges_sum_amount ?? 0,
            $user->coin_logs_sum_obtained_coins ?? 0
        ));
    }

    public function received_rewards(Request $request)
    {
        $now = now();

        $user = User::query()->withSum(['charges' => function ($query) use ($now) {
            $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month);
        }], 'amount')->withSum(['coinLogs' => function ($query) use ($now) {
            $query->whereYear('created_at', $now->year)->whereMonth('created_at', $now->month)->where('status', 1);
        }], 'obtained_coins')->find(auth()->user()->id);
        if ($user->type_user == 3) {
            return Common::apiResponse(0, __('api_responses.notAllowed'));
        }
        $charges_sum_amount = $user->charges_sum_amount ?? 0;
        $coin_logs_sum_obtained_coins = $user->coin_logs_sum_obtained_coins ?? 0;
        $target = ChargeTargetEvent::query()->find($request->target_id);
        if ($target == null) {
            return Common::apiResponse(0, __('api_responses.no_target'));
        }
        $total = $charges_sum_amount + $coin_logs_sum_obtained_coins;

        $alreadyClaimed = UserChargeEvent::query()
            ->where(['user_id' => $user->id, 'charge_event_id' => $target->id])
            ->exists();
        if ($alreadyClaimed) {
            return Common::apiResponse(0, __('you_have_charge_target'));
        }

        if ($total < $target->value) {
            return Common::apiResponse(0, __('api_responses.dont_achieve_taregt'));
        }

        $rewards = RewardTarget::where('charge_event_id', $target->id)->get();
        if ($rewards->isEmpty()) {
            return Common::apiResponse(0, __('api_responses.no_target'));
        }

        try {
            DB::transaction(function () use ($user, $target, $rewards) {
                UserChargeEvent::create([
                    'user_id' => $user->id,
                    'charge_event_id' => $target->id,
                ]);

                foreach ($rewards as $reward) {
                    if ($reward->type == "coins") {
                        UserCoinLogHelper::logByType(
                            $user->id,
                            $reward->target,
                            $user->di,
                            UserCoinLogType::CHARGE_EVENT,
                        );
                        $user->increment('di', $reward->target);
                    } elseif ($reward->type == "vip") {
                        $vip = OVip::query()->find($reward->target);
                        if ($vip) {
                            UserCommon::addVipToUser($user, $vip, $reward->expire, null, 'charge-event');
                        }
                    } elseif ($reward->type == "ware") {
                        $ware = Ware::query()->find($reward->target);
                        if ($ware) {
                            UserCommon::addEvintsWareToUser($user, $ware, $reward->expire, null, 'charge-event');
                        }
                    } elseif ($reward->type == "achievement") {
                        UserAchievementLevel::create([
                            'user_id' => $user->id,
                            'receive_type' => 'charge-event',
                            'custom_achievement_id' => $reward->target,
                            'end_at' => Carbon::parse($reward->expire)->format("Y-m-d H:i:s"),
                        ]);
                    } elseif ($reward->type == 'badge') {
                        Common::userBadge($user->id, $reward->target, $reward->expire, 'charge-event');
                    }
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return Common::apiResponse(0, __('you_have_charge_target'));
            }
            throw $e;
        }

        return Common::apiResponse(1, __('api_responses.added_successfully'));
    }
}