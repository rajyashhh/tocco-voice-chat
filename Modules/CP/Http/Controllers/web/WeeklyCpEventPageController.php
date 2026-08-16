<?php

namespace Modules\CP\Http\Controllers\web;

use App\Models\GiftLog;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\CP\Entities\WeeklyCpGift;
use Modules\CP\Repositories\WeeklyCpRepository;
use Modules\Events\Entities\WeeklyStar;

class WeeklyCpEventPageController extends Controller
{
    public function __construct(private readonly WeeklyCpRepository $weeklyCpRepository) {}

    public function weeklyCpHtml(Request $request)
    {
        $lang = $request->get('lang') === 'ar' ? 'ar' : 'en';
        app()->setLocale($lang);

        $weeklyCp = $this->weeklyCpRepository->currentWeeklyCp();
        $timezone = getTimezone();
        $giftIds = $weeklyCp ? $weeklyCp->gifts->pluck('id')->toArray() : [];

        $windows = $this->windows($weeklyCp, $timezone);

        $tabs = [];
        foreach ($windows as $key => [$start, $end]) {
            $tabs[$key] = $giftIds === [] ? collect() : $this->rankRows($giftIds, $start, $end);
        }

        $userIds = collect($tabs)
            ->flatMap(fn ($rows) => $rows->flatMap(fn ($row) => [$row->cp?->user_one_id, $row->cp?->user_two_id]))
            ->filter()->unique()->values();

        $prev = $this->previousEventData($lang);

        foreach ($prev['winners'] as $winner) {
            $userIds = $userIds->merge([$winner->user_one_id, $winner->user_two_id]);
        }

        $profiles = Profile::whereIn('user_id', $userIds->filter()->unique()->values())
            ->get(['user_id', 'avatar'])->keyBy('user_id');

        $my = $this->currentUserCard($request, $giftIds, $windows);

        return view('weeklyCpEvent', [
            'lang' => $lang,
            'weeklyCp' => $weeklyCp,
            'tabs' => $tabs,
            'profiles' => $profiles,
            'my' => $my,
            'prevWinners' => $prev['winners'],
            'prevRewards' => $prev['rewards'],
        ]);
    }

    public function weeklyCpRewardsHtml(Request $request)
    {
        $prev = $this->previousEventData('en');

        $userIds = collect();
        foreach ($prev['winners'] as $winner) {
            $userIds->push($winner->user_one_id);
            $userIds->push($winner->user_two_id);
        }

        $profiles = Profile::whereIn('user_id', $userIds->filter()->unique()->values())
            ->get(['user_id', 'avatar'])->keyBy('user_id');

        $coins = [1 => 100000, 2 => 50000, 3 => 25000];
        $gifts = $this->weeklyCpRepository->currentWeeklyCp()?->weeklyCpGifts ?? collect();
        foreach ([1, 2, 3] as $level) {
            $gift = $gifts->where('level', $level)->firstWhere('type', 'coins');
            if ($gift && (int) $gift->target > 0) {
                $coins[$level] = (int) $gift->target;
            }
        }

        return view('weeklyCpRewards', [
            'winners' => $prev['winners'],
            'profiles' => $profiles,
            'coins' => $coins,
        ]);
    }

    /**
     * @return array<string, array{0: Carbon, 1: Carbon}>
     */
    private function windows($weeklyCp, string $timezone): array
    {
        $now = Carbon::now($timezone);

        $weeklyStart = $weeklyCp
            ? Carbon::parse($weeklyCp->start_date, $timezone)->startOfDay()
            : $now->copy()->startOfWeek();
        $weeklyEnd = $weeklyCp
            ? Carbon::parse($weeklyCp->end_date, $timezone)->endOfDay()
            : $now->copy()->endOfWeek();

        return [
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$weeklyStart, $weeklyEnd],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        ];
    }

    private function rankRows(array $giftIds, Carbon $start, Carbon $end, int $limit = 20)
    {
        return GiftLog::whereIn('giftId', $giftIds)
            ->select(DB::raw('sum(giftPrice) as totalGiftNum'), 'cp_id')
            ->groupBy('cp_id')
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('cps', fn ($q) => $q->relation())
            ->with('cp')
            ->orderByDesc('totalGiftNum')
            ->limit($limit)
            ->get();
    }

    private function currentUserCard(Request $request, array $giftIds, array $windows): ?array
    {
        $token = $request->query('token');
        if (! $token) {
            return null;
        }

        $tokenRecord = PersonalAccessToken::findToken($token);
        if (! $tokenRecord) {
            return null;
        }

        if ($tokenRecord->expires_at && $tokenRecord->expires_at->isPast()) {
            return null;
        }

        $me = User::find($tokenRecord->tokenable_id);
        if (! $me) {
            return null;
        }

        $cp = $this->weeklyCpRepository->userCP($me->id);

        $base = [
            'name' => $me->name ?? '',
            'avatar' => $this->imageUrl($me->profile?->avatar),
        ];

        if (! $cp) {
            return $base + ['participant' => false, 'ranks' => []];
        }

        $ranks = [];
        foreach ($windows as $key => [$start, $end]) {
            $ranks[$key] = $giftIds === [] ? null : $this->rankOf($giftIds, $cp->id, $start, $end);
        }

        $partnerId = $cp->user_one_id == $me->id ? $cp->user_two_id : $cp->user_one_id;
        $partner = User::find($partnerId);

        return $base + [
            'participant' => true,
            'ranks' => $ranks,
            'partner_name' => $partner?->name ?? '',
            'partner_avatar' => $this->imageUrl($partner?->profile?->avatar),
        ];
    }

    private function rankOf(array $giftIds, int $cpId, Carbon $start, Carbon $end): ?int
    {
        $total = (int) GiftLog::whereIn('giftId', $giftIds)
            ->where('cp_id', $cpId)
            ->whereBetween('created_at', [$start, $end])
            ->sum('giftPrice');

        if ($total <= 0) {
            return null;
        }

        $higher = GiftLog::whereIn('giftId', $giftIds)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('cps', fn ($q) => $q->relation())
            ->select('cp_id')
            ->groupBy('cp_id')
            ->havingRaw('SUM(giftPrice) > ?', [$total])
            ->pluck('cp_id')
            ->count();

        return $higher + 1;
    }

    /**
     * Last completed weekly CP round: podium winners + the rewards that round carried.
     * Falls back to the current round's configured rewards when the previous round
     * kept none (fresh installs).
     *
     * @return array{winners: \Illuminate\Support\Collection, rewards: array<int, \Illuminate\Support\Collection>}
     */
    private function previousEventData(string $lang): array
    {
        $prevEvent = WeeklyStar::previousEvent()->WeeklyCP()
            ->with([
                'WeeklyCpWinners' => fn ($q) => $q->where('type_relation', 'lovely')->orderBy('level'),
                'weeklyCpGifts',
            ])
            ->first();

        $winners = $prevEvent
            ? $prevEvent->WeeklyCpWinners->whereIn('level', [1, 2, 3])->sortBy('level')->values()
            : collect();

        $rewardSource = $prevEvent?->weeklyCpGifts;
        if (! $rewardSource || $rewardSource->isEmpty()) {
            $current = $this->weeklyCpRepository->currentWeeklyCp();
            $rewardSource = $current?->weeklyCpGifts ?? collect();
        }

        $rewards = [];
        foreach ([1, 2, 3] as $level) {
            $rewards[$level] = $rewardSource
                ->where('level', $level)
                ->map(fn ($gift) => $this->rewardChip($gift, $lang))
                ->filter()
                ->values();
        }

        return ['winners' => $winners, 'rewards' => $rewards];
    }

    private function rewardChip(WeeklyCpGift $gift, string $lang): ?array
    {
        $days = $lang === 'ar' ? 'يوم' : 'days';

        switch ($gift->type) {
            case 'coins':
                return [
                    'image' => $this->imageUrl('custom_image/gold_coin_icon.png'),
                    'label' => numToString((int) $gift->target) . ' ' . ($lang === 'ar' ? 'كوينز' : 'Coins'),
                ];
            case 'ware':
                if (! $gift->ware) {
                    return null;
                }
                $type = match ($gift->ware->type) {
                    6 => $lang === 'ar' ? 'إطار دخول' : 'Intro Frame',
                    5 => $lang === 'ar' ? 'فقاعة شات' : 'Bubble Frame',
                    default => $lang === 'ar' ? 'إطار صورة' : 'Avatar Frame',
                };

                return [
                    'image' => $this->imageUrl($gift->ware->show_img),
                    'label' => "{$type} {$gift->expire} {$days}",
                ];
            case 'vip':
                if (! $gift->vip) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($gift->vip->img),
                    'label' => "{$gift->vip->name} {$gift->expire} {$days}",
                ];
            case 'badge':
                if (! $gift->badge) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($gift->badge->image),
                    'label' => ($gift->badge->name ?? ($lang === 'ar' ? 'شارة' : 'Badge')) . " {$gift->expire} {$days}",
                ];
            case 'achievement':
                if (! $gift->customAchievement) {
                    return null;
                }

                return [
                    'image' => $this->imageUrl($gift->customAchievement->images?->first()?->image),
                    'label' => ($lang === 'ar' ? 'إنجاز' : 'Achievement') . " {$gift->expire} {$days}",
                ];
        }

        return null;
    }

    private function imageUrl(?string $path): string
    {
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return getImagePath($path) ?? '';
    }
}