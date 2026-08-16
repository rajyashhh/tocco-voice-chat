<?php

namespace Modules\Events\Http\Controllers\web;

use App\Models\Profile;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Events\Entities\ChargeKingReward;
use Modules\Events\Entities\ChargeKingWinner;
use Modules\Events\Repositories\ChargeKingRepository;

class ChargeKingPageController extends Controller
{
    private const LANGS = ['en', 'ar', 'tr', 'hi', 'id'];

    public function __construct(private readonly ChargeKingRepository $chargeKingRepository) {}

    public function view(Request $request)
    {
        $lang = in_array($request->get('lang'), self::LANGS) ? $request->get('lang') : 'en';
        app()->setLocale($lang);

        $timezone = getTimezone();
        $now = Carbon::now($timezone);
        $fromDate = $now->copy()->startOfMonth()->toDateString();
        $tillDate = $now->copy()->endOfMonth()->toDateString();

        $top = $this->chargeKingRepository->top($fromDate, $tillDate, 10);

        $rewards = $this->rankRewards($lang);

        $prevWinners = $this->previousWinners();

        $userIds = $top->pluck('id')
            ->merge($prevWinners->flatten(1)->pluck('user_id'))
            ->filter()->unique()->values();

        $profiles = Profile::whereIn('user_id', $userIds)
            ->get(['user_id', 'avatar'])->keyBy('user_id');

        $my = $this->currentUserCard($request, $fromDate, $tillDate);

        return view('chargeKingEvent', [
            'lang' => $lang,
            'top' => $top,
            'rewards' => $rewards,
            'prevWinners' => $prevWinners,
            'profiles' => $profiles,
            'my' => $my,
            'endsAtMs' => $now->copy()->endOfMonth()->getTimestampMs(),
        ]);
    }

    /**
     * @return array<int, \Illuminate\Support\Collection>
     */
    private function rankRewards(string $lang): array
    {
        $rows = ChargeKingReward::whereIn('rank', [1, 2, 3])
            ->with(['ware', 'vip', 'badge', 'customAchievement.images'])
            ->get()
            ->groupBy('rank');

        $rewards = [];
        foreach ([1, 2, 3] as $rank) {
            $rewards[$rank] = $rows->get($rank, collect())
                ->map(fn ($reward) => $this->rewardChip($reward, $lang))
                ->filter()
                ->values();
        }

        if ($rewards[1]->isEmpty()) {
            $prize = (int) (Setting::where('key', 'charge_king_prize')->value('value') ?? 100000);
            $fallback = $this->rewardChip(new ChargeKingReward(['rank' => 1, 'type' => 'coins', 'target' => $prize, 'expire' => 0]), $lang);
            if ($fallback) {
                $rewards[1] = collect([$fallback]);
            }
        }

        return $rewards;
    }

    private function rewardChip(ChargeKingReward $reward, string $lang): ?array
    {
        $days = match ($lang) {
            'ar' => 'يوم',
            'tr' => 'gün',
            'hi' => 'दिन',
            'id' => 'hari',
            default => 'days',
        };
        $coinsLabel = match ($lang) {
            'ar' => 'كوينز',
            'tr' => 'Coin',
            'hi' => 'कॉइन',
            'id' => 'Koin',
            default => 'Coins',
        };

        switch ($reward->type) {
            case 'coins':
                return [
                    'image' => $this->imageUrl('custom_image/gold_coin_icon.png'),
                    'label' => numToString((int) $reward->target) . ' ' . $coinsLabel,
                ];
            case 'ware':
                if (!$reward->ware) {
                    return null;
                }
                $wareNames = [
                    6 => ['en' => 'Intro Frame', 'ar' => 'إطار دخول', 'tr' => 'Giriş Çerçevesi', 'hi' => 'एंट्री फ्रेम', 'id' => 'Bingkai Masuk'],
                    5 => ['en' => 'Bubble Frame', 'ar' => 'فقاعة شات', 'tr' => 'Sohbet Balonu', 'hi' => 'चैट बबल', 'id' => 'Gelembung Obrolan'],
                    0 => ['en' => 'Avatar Frame', 'ar' => 'إطار صورة', 'tr' => 'Avatar Çerçevesi', 'hi' => 'अवतार फ्रेम', 'id' => 'Bingkai Avatar'],
                ];
                $names = $wareNames[$reward->ware->type] ?? $wareNames[0];
                $type = $names[$lang] ?? $names['en'];

                return [
                    'image' => $this->imageUrl($reward->ware->show_img),
                    'label' => "{$type} {$reward->expire} {$days}",
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

                $badgeLabel = match ($lang) {
                    'ar' => 'شارة', 'tr' => 'Rozet', 'hi' => 'बैज', 'id' => 'Lencana', default => 'Badge',
                };

                return [
                    'image' => $this->imageUrl($reward->badge->image),
                    'label' => ($reward->badge->name ?? $badgeLabel) . " {$reward->expire} {$days}",
                ];
            case 'achievement':
                if (!$reward->customAchievement) {
                    return null;
                }
                $achievementLabel = match ($lang) {
                    'ar' => 'إنجاز', 'tr' => 'Başarı', 'hi' => 'उपलब्धि', 'id' => 'Pencapaian', default => 'Achievement',
                };

                return [
                    'image' => $this->imageUrl($reward->customAchievement->images?->first()?->image),
                    'label' => "{$achievementLabel} {$reward->expire} {$days}",
                ];
        }

        return null;
    }

    /**
     * Last 3 crowned months from charge_king_winners, newest first.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    private function previousWinners()
    {
        $months = collect(range(1, 3))
            ->map(fn ($i) => Carbon::now()->subMonths($i)->format('Y-m'));

        return ChargeKingWinner::whereIn('month', $months)
            ->with('user')
            ->orderByDesc('month')
            ->orderBy('rank')
            ->get()
            ->groupBy('month');
    }

    private function currentUserCard(Request $request, string $fromDate, string $tillDate): ?array
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

        $total = $this->chargeKingRepository->totalFor($me->id, $fromDate, $tillDate);
        $rank = $this->chargeKingRepository->rankOf($total, $fromDate, $tillDate);
        $nextHigher = $this->chargeKingRepository->nextHigherTotal($total, $fromDate, $tillDate);

        return [
            'name' => $me->name ?? '',
            'avatar' => $this->imageUrl($me->profile?->avatar),
            'points' => $total,
            'rank' => $rank,
            'gap' => $nextHigher === null ? null : max(0, $nextHigher - $total),
        ];
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