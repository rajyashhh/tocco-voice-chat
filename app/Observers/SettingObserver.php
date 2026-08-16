<?php

namespace App\Observers;

use App\Helpers\CacheHelper;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        $this->clearSettingCache($setting);
        $this->bumpThemeTimestamps($setting);
        $this->refreshCache();
    }

    public function deleted(Setting $setting): void
    {
        $this->clearSettingCache($setting);
        $this->refreshCache();
    }

    protected function clearSettingCache(Setting $setting): void
    {
        Cache::forget($setting->key);
        Cache::forget("settings.{$setting->key}");
    }

    /**
     * Exact color/theme Setting keys the mobile app consumes (colors/v2 +
     * app-check). Shared by the cache flush and the app-sync timestamp bump so
     * the two lists never drift apart.
     */
    protected function themeKeys(): array
    {
        return [
            'primary_color', 'secondary_color', 'text_primary_color',
            'text_secondary_color', 'box_background_color', 'app_background',
            'brand_background_image', 'table_background_color', 'dark_mode',
            'app_primary_color', 'background_color', 'bottom_nav_bottom_color',
            'bottom_nav_active_color', 'bottom_nav_inactive_color',
            'text_header_color', 'button_text_color', 'icon_color', 'card_color',
            'dark_mode_color', 'light_mode_color',
            // ColorToken siblings — the API caches each '{key}_grad' for 1h, so
            // it must be flushed on save for the new token to take effect.
            'app_primary_color_grad', 'text_header_color_grad',
            'button_text_color_grad', 'text_primary_color_grad',
            'text_secondary_color_grad', 'icon_color_grad', 'card_color_grad',
        ];
    }

    /**
     * A color/theme save must make the mobile refetch its theme exactly once.
     * The app-check (VersionController) compares settings()->get('<key>') vs the
     * client's stored time, so we bump those same keys to NOW via the SAME store
     * the check reads (settings.json). This is the single place that covers every
     * write path uniformly — Encore admin CRUD, SettingsController::update(),
     * updateAppConfig() — because they all persist through Setting::save().
     *
     * Only color/theme keys trigger a bump: a non-color settings write must not
     * force every client to refetch. A timestamp bump is white-label safe (it
     * only schedules a one-time refetch of already-published values).
     */
    protected function bumpThemeTimestamps(Setting $setting): void
    {
        $key = (string) $setting->key;
        if (!$this->isThemeKey($key)) {
            return;
        }

        $now = Carbon::now()->timestamp;

        // Drives cache_update.colors + cache_update.color_time in app-check.
        settings()->set('colors_updated_at', $now);
        settings()->set('color_setting_updated_at', $now);

        // Background/gradient/region edits also drive cache_update.background.
        if ($this->isBackgroundKey($key)) {
            settings()->set('ground_updated_at', $now);
        }
    }

    /**
     * Whether a saved key is a color/theme key the app renders. Matches the
     * exact list above plus the naming patterns used across the theme settings
     * (any *_color, background_*, gradient_*, nav_* / bottom_nav_*, dark/light
     * mode, the unified body_bg/nav_bg JSON regions). Deliberately scoped to
     * visual keys so ordinary settings writes never trigger a refetch.
     */
    protected function isThemeKey(string $key): bool
    {
        if (in_array($key, $this->themeKeys(), true)) {
            return true;
        }

        return Str::endsWith($key, '_color')
            // ColorToken siblings + raw token sub-keys. The persisted sibling is
            // '{key}_grad' (a color/theme JSON token); the loose
            // {key}_type/_colors/_direction/_reverse inputs are stripped before
            // save, but match them too so any write path still bumps the
            // timestamp and the app refetches the theme exactly once.
            || Str::endsWith($key, ['_grad', '_direction', '_reverse'])
            || Str::endsWith($key, ['_color_type', '_color_colors'])
            || Str::startsWith($key, ['background_', 'gradient_', 'nav_', 'bottom_nav_'])
            || Str::startsWith($key, ['dark_mode', 'light_mode'])
            || in_array($key, ['body_bg', 'nav_bg', 'background_type', 'app_background'], true);
    }

    /**
     * Whether the key affects the app background (so cache_update.background is
     * bumped too). The colors timestamps are still bumped for these — a
     * background edit is part of the theme.
     */
    protected function isBackgroundKey(string $key): bool
    {
        return Str::startsWith($key, ['background_', 'gradient_'])
            || in_array($key, ['body_bg', 'nav_bg', 'app_background', 'background_color', 'background_type'], true);
    }

    protected function refreshCache(): void
    {
        Cache::forget('all_settings');

        foreach ($this->themeKeys() as $key) {
            Cache::forget($key);
        }

        Cache::forget('app_title');
        Cache::forget('app_title_en');
        Cache::forget('app_title_ar');
        Cache::forget('settings.app_title_en');
        Cache::forget('settings.app_title_ar');
        Cache::forget('appLogo');
        Cache::forget('favicon');
        Cache::forget('exchange_coin_percentage');

        // White-label identity / endpoint / storage keys. Each is read via
        // Common::getSettingValue (rememberForever under the bare key), so it must
        // be flushed when the admin saves the white-label page for the new value
        // to take effect without a deploy. (Storage creds also require a fresh
        // request to rebuild the gcs disk in AppServiceProvider::boot.)
        $whiteLabelKeys = [
            'fcm_sender_id', 'google_client_id', 'utd_secret_key', 'utd_client_id',
            'app_origin_name',
            'firebase_database_uri', 'firebase_project_name', 'notification_default_image',
            'google_play_package_name', 'centrifugo_api_url', 'centrifugo_api_key',
            'centrifugo_hmac_secret', 'centrifugo_proxy_secret', 'utd_media_analyze_url',
            'gcs_service_account_json', 'gcs_bucket', 'gcs_project_id',
            // Firebase app identity (served by /firebase-config, cached 300s).
            'firebase_web_api_key', 'firebase_auth_domain', 'firebase_phone_auth_enabled',
            'firebase_app_android_api_key', 'firebase_app_android_app_id',
            'firebase_app_android_sender_id', 'firebase_app_android_project_id',
            'firebase_app_android_bucket',
            'firebase_app_ios_api_key', 'firebase_app_ios_app_id',
            'firebase_app_ios_sender_id', 'firebase_app_ios_project_id',
            'firebase_app_ios_bucket',
            // Firebase WEB app identity (served by /firebase-config). Same flush
            // path so a panel save takes effect without a deploy.
            'firebase_app_web_api_key', 'firebase_app_web_app_id',
            'firebase_app_web_sender_id', 'firebase_app_web_project_id',
            'firebase_app_web_bucket', 'firebase_app_web_measurement_id',
            // Phone OTP provider switch + Twilio SMS credentials (read via
            // Common::getSettingValue by OtpProviderService / TwilioSms).
            'phone_otp_provider', 'twilio_account_sid', 'twilio_auth_token',
            'twilio_from_number',
        ];
        foreach ($whiteLabelKeys as $key) {
            Cache::forget($key);
        }

        // The /firebase-config response is cached as one blob; flush it so a
        // panel save of any Firebase identity key takes effect without a deploy.
        Cache::forget('firebase_config');

        CacheHelper::cacheSettings();
    }
}
