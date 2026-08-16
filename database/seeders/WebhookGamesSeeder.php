<?php

namespace Database\Seeders;


use App\Models\GameProviderSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;


class WebhookGamesSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'provider_code' => 'utd',
                'provider_name' => 'UTD Games',
                'webhook_routes' => [
                    'get_user_info'  => config('app.url') . '/api/leader-cc-game/get-user-info',
                    'make_up_orders' => config('app.url') . '/api/leader-cc-game/make-up-orders',
                    'change_balance' => config('app.url') . '/api/leader-cc-game/change-balance',
                ],
            ],
        ];

        foreach ($providers as $provider) {

            $gameSetting = GameProviderSetting::updateOrCreate(
                ['provider_code' => $provider['provider_code']], // condition
                $provider // values
            );

            // 🔥 Refresh cache
            $cacheKey = 'game_provider_' . $provider['provider_code'];

            Cache::forget($cacheKey);
            Cache::put($cacheKey, $gameSetting);
        }
    }
}
