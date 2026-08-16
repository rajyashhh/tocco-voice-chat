<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\GiftLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkReward;
use Modules\Events\Entities\PkWinner;

class PkEventPageController extends Controller
{
    private const CATEGORIES = [
        'king' => ['column' => 'sender_id', 'relation' => 'sender', 'pk_type' => 'pk-king'],
        'star' => ['column' => 'receiver_id', 'relation' => 'receiver', 'pk_type' => 'pk-star'],
        'room' => ['column' => 'roomowner_id', 'relation' => 'roomOwner', 'pk_type' => 'pk-room'],
    ];

    public function view(Request $request)
    {
        $lang = in_array($request->get('lang'), ['en', 'ar', 'tr', 'hi', 'id'], true)
            ? $request->get('lang')
            : 'en';
        app()->setLocale($lang);

        $pkEvent = PkEvent::currentEvent()->with('rewards')->first();

        $tabs = [];
        foreach (self::CATEGORIES as $key => $meta) {
            $tabs[$key] = $pkEvent ? $this->leaderboard($pkEvent, $meta) : collect();
        }

        $rewards = [];
        foreach (self::CATEGORIES as $key => $meta) {
            $rewards[$key] = $this->rewardsOf($pkEvent, $meta['pk_type'], $lang);
        }

        $prevWinners = $this->previousWinners();
        $my = $this->currentUserCard($request, $pkEvent);

        $endsAt = $pkEvent
            ? Carbon::parse($pkEvent->getRawOriginal('end_date'), getTimezone())->endOfDay()->timestamp
            : null;

        return view('pkEvent', [
            'lang' => $lang,
            'pkEvent' => $pkEvent,
            'tabs' => $tabs,
            'rewards' => $rewards,
            'prevWinners' => $prevWinners,
            'my' => $my,
            'endsAt' => $endsAt,
        ]);
    }

    private function leaderboard(PkEvent $pkEvent, array $meta, int $limit = 10)
    {
        $column = $meta['column'];
        $relation = $meta['relation'];

        $query = GiftLog::query()
            ->select(DB::raw('SUM(giftPrice) as totalGiftNum'), $column)
            ->where('pk', 1)
            ->whereBetween('created_at', [$pkEvent->start_date, $pkEvent->end_date])
            ->groupBy($column)
            ->orderByDesc('totalGiftNum')
            ->limit($limit);

        if ($column === 'roomowner_id') {
            $query->where('roomowner_id', '!=', 0)
                ->with(['roomOwner' => function ($q) {
                    $q->select('id', 'name', 'uuid')
                        ->with(['profile:user_id,avatar', 'ownerRoom:id,uid,room_name,room_cover']);
                }]);
        } else {
            $query->with([$relation => function ($q) {
                $q->select('id', 'name', 'uuid')->with('profile:user_id,avatar');
            }]);
        }

        return $query->get()->map(function ($row) use ($relation, $column) {
            $user = $row->getRelation($relation);
            $isRoom = $column === 'roomowner_id';

            return [
                'user_id' => $row->{$column},
                'name' => $isRoom
                    ? ($user?->ownerRoom?->room_name ?: ($user?->name ?? ''))
                    : ($user?->name ?? ''),
                'avatar' => $this->imageUrl($isRoom
                    ? ($user?->ownerRoom?->room_cover ?: $user?->profile?->avatar)
                    : $user?->profile?->avatar),
                'total' => (int) $row->totalGiftNum,
            ];
        });
    }

    private function rewardsOf(?PkEvent $pkEvent, string $pkType, string $lang): array
    {
        $rewards = [1 => collect(), 2 => collect(), 3 => collect()];
        if (!$pkEvent) {
            return $rewards;
        }

        foreach ([1, 2, 3] as $level) {
            $rewards[$level] = $pkEvent->rewards
                ->where('pk_type', $pkType)
                ->where('level', $level)
                ->map(fn ($reward) => $this->rewardChip($reward, $lang))
                ->filter()
                ->values();
        }

        return $rewards;
    }

    private function rewardChip(PkReward $reward, string $lang): ?array
    {
        $days = match ($lang) {
            'ar' => 'يوم',
            'tr' => 'gün',
            'hi' => 'दिन',
            'id' => 'hari',
            default => 'days',
        };

        switch ($reward->type) {
            case 'coins':
                $coinsLabel = match ($lang) {
                    'ar' => 'كوينز',
                    'tr' => 'Coin',
                    'hi' => 'कॉइन्स',
                    'id' => 'Koin',
                    default => 'Coins',
                };

                return [
                    'image' => $this->imageUrl('custom_image/gold_coin_icon.png'),
                    'label' => numToString((int) $reward->target) . ' ' . $coinsLabel,
                ];
            case 'ware':
                if (!$reward->ware) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->ware->show_img),
                    'label' => trim(($reward->ware->name ?? '') . " {$reward->expire} {$days}"),
                ];
            case 'vip':
                if (!$reward->vip) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->vip->img),
                    'label' => "{$reward->vip->name} {$reward->expire} {$days}",
                ];
            case 'badge':
                if (!$reward->badge) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->badge->image),
                    'label' => trim(($reward->badge->name ?? '') . " {$reward->expire} {$days}"),
                ];
            case 'achievement':
                if (!$reward->customAchievement) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->customAchievement->images?->first()?->image),
                    'label' => trim(($reward->customAchievement->name ?? '') . " {$reward->expire} {$days}"),
                ];
        }

        return null;
    }

    /**
     * @return array<string, \Modules\Events\Entities\PkWinner|null> keyed by category
     */
    private function previousWinners(): array
    {
        $winners = ['king' => null, 'star' => null, 'room' => null];

        $prevEvent = PkEvent::previousEvent()->first();
        if (!$prevEvent) {
            return $winners;
        }

        $rows = PkWinner::with(['winner' => function ($q) {
            $q->select('id', 'name', 'uuid')
                ->with(['profile:user_id,avatar', 'ownerRoom:id,uid,room_name,room_cover']);
        }])
            ->where('pk_event_id', $prevEvent->id)
            ->where('level', 1)
            ->get()
            ->keyBy('pk_type');

        foreach (self::CATEGORIES as $key => $meta) {
            $winners[$key] = $rows->get($meta['pk_type']);
        }

        return $winners;
    }

    private function currentUserCard(Request $request, ?PkEvent $pkEvent): ?array
    {
        $token = $request->query('token');
        if (!$token) {
            return null;
        }

        $tokenRecord = PersonalAccessToken::findToken($token);
        if (!$tokenRecord) {
            return null;
        }

        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            return null;
        }

        $me = User::find($tokenRecord->tokenable_id);
        if (!$me) {
            return null;
        }

        $ranks = ['king' => null, 'star' => null, 'room' => null];
        if ($pkEvent) {
            foreach (self::CATEGORIES as $key => $meta) {
                $ranks[$key] = $this->rankOf($pkEvent, $meta['column'], $me->id);
            }
        }

        return [
            'name' => $me->name ?? '',
            'avatar' => $this->imageUrl($me->profile?->avatar),
            'ranks' => $ranks,
        ];
    }

    private function rankOf(PkEvent $pkEvent, string $column, int $userId): ?int
    {
        $base = GiftLog::where('pk', 1)
            ->whereBetween('created_at', [$pkEvent->start_date, $pkEvent->end_date]);

        if ($column === 'roomowner_id') {
            $base->where('roomowner_id', '!=', 0);
        }

        $total = (int) (clone $base)->where($column, $userId)->sum('giftPrice');
        if ($total <= 0) {
            return null;
        }

        $higher = (clone $base)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('SUM(giftPrice) > ?', [$total])
            ->pluck($column)
            ->count();

        return $higher + 1;
    }

    private function imageUrl(?string $path): string
    {
        if (!$path) {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return getImagePath($path) ?? '';
    }
}