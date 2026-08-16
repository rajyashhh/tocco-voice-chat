<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Helpers\UserCommon;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;

class UserStatisticsController extends Controller
{
    public function index(Content $content)
    {
        $query = trim((string) request('q'));
        $user = null;
        $matches = collect();

        if (request()->filled('user')) {
            $user = User::where('uuid', request('user'))
                ->with(['profile', 'country'])
                ->first();
        } elseif ($query !== '') {
            $matches = User::with(['profile', 'country'])
                ->where('uuid', $query)
                ->orWhere('name', 'like', "%{$query}%")
                ->limit(15)
                ->get();

            if ($matches->count() === 1) {
                $user = $matches->first();
                $matches = collect();
            }
        }

        $stats = $user ? $this->buildStats($user) : null;

        return $content
            ->title(trans('User Statistics'))
            ->body(view('admin.user_statistics', [
                'query'   => $query,
                'user'    => $user,
                'matches' => $matches,
                'stats'   => $stats,
            ]));
    }

    private function buildStats(User $user): array
    {
        $data = (new UserCommon())->userMoreStatistics($user);

        $giftsSentValue = (float) ($data['losed']['gift_logs'] ?? 0);
        $giftsReceivedValue = (float) $user->giftLogs()->sum('giftPrice');

        $gamesWon = (float) $user->coinGameUser()->where('type', 1)->sum('coins');
        $gamesLost = (float) $user->coinGameUser()->where('type', 0)->sum('coins');
        $gamesPlayed = (int) $user->coinGameUser()->count();

        $roomsCount = (int) $user->rooms()->count();

        $totalCharges = (float) ($data['earned']['charges'] ?? 0);
        $coinLogsObtained = (float) ($data['earned']['coin_logs'] ?? 0);

        $cards = [
            [
                'label' => trans('Current Balance'),
                'value' => (int) ($user->di ?? 0),
                'icon'  => 'fa-coins',
                'color' => '#2563eb',
            ],
            [
                'label' => trans('Total Recharges'),
                'value' => $totalCharges,
                'icon'  => 'fa-credit-card',
                'color' => '#16a34a',
            ],
            [
                'label' => trans('Coins Obtained'),
                'value' => $coinLogsObtained,
                'icon'  => 'fa-database',
                'color' => '#0891b2',
            ],
            [
                'label' => trans('Gifts Sent'),
                'value' => $giftsSentValue,
                'icon'  => 'fa-gift',
                'color' => '#db2777',
            ],
            [
                'label' => trans('Gifts Received'),
                'value' => $giftsReceivedValue,
                'icon'  => 'fa-gifts',
                'color' => '#9333ea',
            ],
            [
                'label' => trans('Rooms'),
                'value' => $roomsCount,
                'icon'  => 'fa-microphone',
                'color' => '#ea580c',
            ],
            [
                'label' => trans('Game Winnings'),
                'value' => $gamesWon,
                'sub'   => $gamesPlayed . ' ' . trans('rounds'),
                'icon'  => 'fa-gamepad',
                'color' => '#16a34a',
            ],
            [
                'label' => trans('Game Losses'),
                'value' => $gamesLost,
                'icon'  => 'fa-gamepad',
                'color' => '#dc2626',
            ],
        ];

        return [
            'cards' => $cards,
            'breakdown' => [
                trans('charges')       => (float) ($data['earned']['charges'] ?? 0),
                trans('coin_logs')     => (float) ($data['earned']['coin_logs'] ?? 0),
                trans('exchange_logs') => (float) ($data['earned']['exchange_logs'] ?? 0),
                trans('coin_games')    => (float) ($data['earned']['coin_games'] ?? 0),
                trans('lucky_gifts')   => (float) ($data['earned']['lucky_gifts'] ?? 0),
            ],
            'spending' => [
                trans('packs')                     => (float) ($data['losed']['packs'] ?? 0),
                trans('gift_logs')                 => (float) ($data['losed']['gift_logs'] ?? 0),
                trans('coin_games')                => (float) ($data['losed']['coin_games'] ?? 0),
                trans('request_background_images') => (float) ($data['losed']['request_background_images'] ?? 0),
                trans('total vip price')           => (float) ($data['losed']['vip_price'] ?? 0),
            ],
            'totals' => [
                'earned' => (float) ($data['total']['earned'] ?? 0),
                'losed'  => (float) ($data['total']['losed'] ?? 0),
            ],
        ];
    }
}
