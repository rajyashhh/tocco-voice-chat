<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\GiftLog;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Events\Entities\Reward;
use Modules\Events\Entities\WeeklyStar;
use Modules\Events\Entities\Winner;

class WeeklyStarPageController extends Controller
{
    private const LANGS = ['en', 'ar', 'tr', 'hi', 'id'];

    public function view(Request $request)
    {
        $lang = in_array($request->get('lang'), self::LANGS, true) ? $request->get('lang') : 'en';
        app()->setLocale($lang);

        $weeklyEvent = WeeklyStar::currentEvent()->weeklyStar()
            ->with('gifts', 'rewards.ware', 'rewards.vip', 'rewards.badge', 'rewards.customAchievement')
            ->orderByDesc('start_date')
            ->first();

        $giftIds = $weeklyEvent ? $weeklyEvent->gifts->pluck('id')->toArray() : [];

        $top = $giftIds === []
            ? collect()
            : GiftLog::whereIn('giftId', $giftIds)
                ->whereBetween('created_at', [$weeklyEvent->start_date, $weeklyEvent->end_date])
                ->select(DB::raw('SUM(giftPrice) as totalGiftNum'), 'sender_id')
                ->with('sender')
                ->groupBy('sender_id')
                ->orderByDesc('totalGiftNum')
                ->limit(10)
                ->get();

        $prevWinners = $this->previousWinners();

        $userIds = $top->pluck('sender_id')
            ->merge($prevWinners->pluck('user_id'))
            ->filter()->unique()->values();

        $profiles = Profile::whereIn('user_id', $userIds)
            ->get(['user_id', 'avatar'])->keyBy('user_id');

        $rewards = $this->rewardChips($weeklyEvent);

        $my = $this->currentUserCard($request, $weeklyEvent, $giftIds);

        $endsAt = $weeklyEvent
            ? Carbon::parse($weeklyEvent->end_date, 'UTC')->getTimestampMs()
            : null;

        return view('weeklyStarEvent', [
            'lang' => $lang,
            'weeklyEvent' => $weeklyEvent,
            'top' => $top,
            'profiles' => $profiles,
            'rewards' => $rewards,
            'my' => $my,
            'prevWinners' => $prevWinners,
            'endsAt' => $endsAt,
        ]);
    }

    private function previousWinners()
    {
        $prevEvent = WeeklyStar::previousEvent()->weeklyStar()->first();

        if (!$prevEvent) {
            return collect();
        }

        return Winner::where('weekly_star_id', $prevEvent->id)
            ->whereIn('level', [1, 2, 3])
            ->with('user')
            ->orderBy('level')
            ->get();
    }

    /**
     * @return array<int, \Illuminate\Support\Collection>
     */
    private function rewardChips(?WeeklyStar $weeklyEvent): array
    {
        $chips = [1 => collect(), 2 => collect(), 3 => collect()];

        if (!$weeklyEvent) {
            return $chips;
        }

        foreach ([1, 2, 3] as $level) {
            $chips[$level] = $weeklyEvent->rewards
                ->where('level', $level)
                ->map(fn ($reward) => $this->rewardChip($reward))
                ->filter()
                ->values();
        }

        return $chips;
    }

    private function rewardChip(Reward $reward): ?array
    {
        switch ($reward->type) {
            case 'coins':
                return [
                    'image' => $this->imageUrl('custom_image/gold_coin_icon.png'),
                    'kind' => 'coins',
                    'amount' => (int) $reward->target,
                    'days' => null,
                    'name' => null,
                ];
            case 'ware':
                if (!$reward->ware) {
                    return null;
                }
                $kind = match ($reward->ware->type) {
                    6 => 'intro_frame',
                    5 => 'bubble_frame',
                    default => 'avatar_frame',
                };

                return [
                    'image' => $this->imageUrl($reward->ware->show_img),
                    'kind' => $kind,
                    'amount' => null,
                    'days' => (int) $reward->expire,
                    'name' => null,
                ];
            case 'vip':
                if (!$reward->vip) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->vip->img),
                    'kind' => 'vip',
                    'amount' => null,
                    'days' => (int) $reward->expire,
                    'name' => $reward->vip->name,
                ];
            case 'badge':
                if (!$reward->badge) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->badge->images?->firstWhere('language', app()->getLocale())?->image
                        ?? $reward->badge->images?->first()?->image),
                    'kind' => 'badge',
                    'amount' => null,
                    'days' => (int) $reward->expire,
                    'name' => $reward->badge->name,
                ];
            case 'achievement':
                if (!$reward->customAchievement) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($reward->customAchievement->images?->firstWhere('language', app()->getLocale())?->image
                        ?? $reward->customAchievement->images?->first()?->image),
                    'kind' => 'achievement',
                    'amount' => null,
                    'days' => (int) $reward->expire,
                    'name' => $reward->customAchievement->name,
                ];
        }

        return null;
    }

    private function currentUserCard(Request $request, ?WeeklyStar $weeklyEvent, array $giftIds): ?array
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

        $base = [
            'name' => $me->name ?? '',
            'avatar' => $this->imageUrl($me->profile?->avatar),
        ];

        if (!$weeklyEvent || $giftIds === []) {
            return $base + ['total' => 0, 'rank' => null, 'gap' => null];
        }

        $total = (int) GiftLog::whereIn('giftId', $giftIds)
            ->where('sender_id', $me->id)
            ->whereBetween('created_at', [$weeklyEvent->start_date, $weeklyEvent->end_date])
            ->sum('giftPrice');

        if ($total <= 0) {
            return $base + ['total' => 0, 'rank' => null, 'gap' => null];
        }

        $higherTotals = GiftLog::whereIn('giftId', $giftIds)
            ->whereBetween('created_at', [$weeklyEvent->start_date, $weeklyEvent->end_date])
            ->select('sender_id', DB::raw('SUM(giftPrice) as total'))
            ->groupBy('sender_id')
            ->havingRaw('SUM(giftPrice) > ?', [$total])
            ->pluck('total');

        $rank = $higherTotals->count() + 1;
        $gap = $rank > 1 ? (int) $higherTotals->min() - $total : null;

        return $base + ['total' => $total, 'rank' => $rank, 'gap' => $gap];
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