<?php

namespace App\Observers;

use App\Models\GameProviderSetting;
use Illuminate\Support\Facades\Cache;

class GameProviderSettingObserver
{
    /**
     * Centralized invalidation for the long-lived game_provider_{code} cache
     * (Common::getByCode). Any write to a provider row — panel save, seeder,
     * console command — forgets the matching key so the next read rebuilds with
     * fresh creds. This is the safety net behind the explicit forget+put in
     * AllGameController::gameSettings.
     */
    public function saved(GameProviderSetting $setting): void
    {
        $this->forget($setting);
    }

    public function deleted(GameProviderSetting $setting): void
    {
        $this->forget($setting);
    }

    private function forget(GameProviderSetting $setting): void
    {
        // Forget by both the new and original provider_code so a code rename
        // (rare) invalidates the previously cached entry too.
        Cache::forget('game_provider_' . $setting->provider_code);

        $original = $setting->getOriginal('provider_code');
        if ($original && $original !== $setting->provider_code) {
            Cache::forget('game_provider_' . $original);
        }
    }
}
