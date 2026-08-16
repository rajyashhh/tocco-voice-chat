<?php

namespace App\Http\Controllers;

use App\helper\TimeHelper;
use Log;
use Cache;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use App\Models\Config;
use App\Models\Target;
use App\Helpers\Common;
use App\Models\Setting;
use App\Models\Timezone;
use App\Models\BrandImage;
use App\Models\PaymentCoin;
use App\Models\UserSallary;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use App\Jobs\ChangeCinemaModeJob;
use App\Models\Language;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\File;
use App\Models\MonthlyDiamondReceive;
use App\Models\NotificationTranslation;

class SettingsController extends Controller
{
    public $permission_name = 'settings';
    public function downloadApp($id)
    {

        $url = env('DOWNLOAD_URL');
        return view('downloadApp', compact('url'));
    }
    public function update(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        // ── Game provider save fallback ─────────────────────────────────────
        // The game-provider cards (per active provider) are their OWN
        // <form action="admin/game-provider-setting">. But those cards live
        // INSIDE the Third-Party settings pane, which on some deployments is
        // still wrapped by an outer app-settings <form>. HTML forbids nested
        // <form>s, so the browser drops the inner form yet KEEPS its inputs —
        // and the toggle's `$(form).submit()` then posts provider_code/app_key/
        // active to THIS route (app-settings/update) instead of the dedicated
        // handler. Detect that here and route it to the same persistence logic
        // as AllGameController@gameSettings so activation works regardless of
        // the surrounding form markup (no server-side blade re-deploy needed).
        if ($request->filled('provider_code')) {
            return $this->saveGameProvider($request);
        }

        $data = $request->except(['_token', 'current_tab', 'inner_tab_type']);

        if (
            ($request->has('shipping_coins') && !is_null($request->shipping_coins) && $request->shipping_coins != cache()->get('shipping_coins')) ||
            ($request->has('super_admin_coins') && !is_null($request->super_admin_coins) && $request->super_admin_coins != cache()->get('super_admin_coins')) ||
            ($request->has('zones_coins') && !is_null($request->zones_coins) && $request->zones_coins != cache()->get('zones_coins')) ||
            ($request->has('user_coins') && !is_null($request->user_coins) && $request->user_coins != cache()->get('user_coins'))
        ) {

            $zoneSetting = Setting::where('key', 'zones_coins')->first();
            $superAdminSetting = Setting::where('key', 'super_admin_coins')->first();
            $shippingSetting = Setting::where('key', 'shipping_coins')->first();
            if ($zoneSetting && $superAdminSetting && $shippingSetting) {

                $userSalary = UserSallary::select('sallary', 'cut_amount')->first();

                // if ($userSalary) {
                //     $calculatedValue = $userSalary->sallary - $userSalary->cut_amount;

                //     if ($calculatedValue > 0) {

                //             admin_toastr(__('We can`t update the target system right now because some users still have active targets.'), 'error');
                //             return back();

                //     }
                // }

                // if ($request->zones_coins < $request->super_admin_coins) {
                //     admin_toastr(__('Zones coins must be greater than  super admin coins'), 'error');
                //     return back();
                // }

                // if ($request->super_admin_coins < $request->shipping_coins) {
                //     admin_toastr(__('super admin coins must be greater than  agancy coins'), 'error');
                //     return back();
                // }

                if ($request->shipping_coins < $request->user_coins) {
                    admin_toastr(__('agancy coins must be greater than  user coins'), 'error');
                    return back();
                }
            }
        }



        // Panel/app background + brand background inputs were removed with the
        // Theme settings tab (colors are fixed in code now). Strip any stray
        // posts of those legacy fields so they are never persisted; the route
        // itself stays tolerant of them (no 500 on an old cached form).
        unset(
            $data['background_type'], $data['background_color'],
            $data['gradient_1'], $data['gradient_2'], $data['gradient_3'],
            $data['brand_background_type'], $data['brand_background_image'],
            $data['brand_image'], $data['box_background_color'], $data['dark_mode']
        );

        if ($request->app_title_en || $request->app_title_ar) {
            Cache::forget('app_title');
        }

        if ($request->hasFile('apple_service_file')) {
            $file_path = Common::upload('images', $request->apple_service_file);
            $data['apple_service_file'] = $file_path;
        }

        // Coin (currency) image: besides persisting the `coin_image` setting,
        // mirror the bytes to the two canonical storage paths every API
        // resource/report already references relative to storage_url
        // (`coin.png` + `custom_image/gold_coin_icon.png`), so the coin icon
        // lights up app-wide without touching each resource.
        if ($request->hasFile('coin_image')) {
            $coinPath = Common::upload('images', $request->file('coin_image'));
            $data['coin_image'] = $coinPath;
            $disk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'));
            $coinBytes = $disk->get($coinPath);
            foreach (['coin.png', 'custom_image/gold_coin_icon.png'] as $canonical) {
                $disk->put($canonical, $coinBytes);
            }
        }
        unset($data['app_background_image'], $data['brand_background_image_reset']);

        if ($request->has('timezone') || $request->has('week_start') || $request->has('week_end')) {
            TimeHelper::clearCache();
        }

        // Process and save settings.
        // App-sync timestamps (colors_updated_at / color_setting_updated_at /
        // ground_updated_at) are bumped centrally in SettingObserver::saved on
        // the Setting::updateOrCreate calls below — as a proper Unix timestamp,
        // the format VersionController::isUpdated compares against. The old
        // per-key settings()->set($cacheKey, true) wrote a BOOLEAN (true), which
        // the app-check coerced to 1 and never saw as newer, so the mobile never
        // refetched colors. It also polluted settings.json with a *_updated_at
        // boolean for every non-color key. Removed.
        foreach ($data as $key => $value) {

            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $value = Common::upload('images', $value);
            }

            if ($request->youtube_status == 0) {
                dispatch(new ChangeCinemaModeJob());

                // Room::where('mode', 5)->update(['mode' => 1]);
            }

            if ($key === 'app_fav_icon') {
                Cache::forget('favicon');
            }

            // Single write per key, guarded so a null value never overwrites an
            // existing setting with NULL. Forget-then-put keeps the per-key cache
            // consistent with the freshly persisted value.
            if (!is_null($value)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                Cache::forget($key);
                Cache::put($key, $value);
            }
        }

        if ($request->payment_getaway_id) {
            $paymentGetaway = PaymentCoin::find($request->payment_getaway_id);
            $key = "is_{$paymentGetaway->type}_active";

            $paymentGetaway->status = $request->$key;
            $paymentGetaway->save();
        }


        if ($request->has('user_coins')) {
            Config::query()->where('name', '=', 'one_usd_value_in_coins')->update(['value' => $request->user_coins]);
        }
        if ($request->has('default_language')) {
            Language::query()->update(['is_default' => 0]);

            Language::where('code', $request->default_language)->update(['is_default' => 1]);
        }

        // Targeted invalidation only — never Cache::flush(). A global flush wipes
        // sessions/roles/every rememberForever key across a shared Redis/Octane
        // process on every settings save. Bust exactly the settings caches:
        //  - the aggregate rememberForever keys (CacheHelper::cacheSettings /
        //    cacheConfig) read by VersionController + setupAppSettings,
        //  - the per-locale app-title keys primed in
        //    AppServiceProvider::setupAppSettings,
        //  - the individual theme/color keys (critical for Octane).
        Cache::forget('all_settings');
        Cache::forget('all_configs');
        Cache::forget('settings.app_title_ar');
        Cache::forget('settings.app_title_en');

        $themeKeys = [
            'primary_color', 'secondary_color', 'text_primary_color',
            'text_secondary_color', 'box_background_color', 'app_background',
            'brand_background_image', 'table_background_color', 'dark_mode',
            'brand_background_type', 'box_background_color',
        ];
        foreach ($themeKeys as $key) {
            Cache::forget($key);
        }

        $redirectUrl = url(config('admin.route.prefix') . '/settings');

        if ($request->has('current_tab')) {
            $redirectUrl .= '?tab=' . $request->current_tab;
            if ($request->has('inner_tab_type')) {
                $redirectUrl .= '&type=' . $request->inner_tab_type;
            }
             if ($request->has('inner_tab_type_hash')) {
                $redirectUrl .= '#' . $request->inner_tab_type_hash;
            }
            admin_toastr(__('Settings updated successfully!'), 'success');
            return redirect($redirectUrl);
        }

        if ($request->has('audio_room')) {
            admin_toastr(__('Settings updated successfully!'), 'success');
            $url = url('admin/default-app-screen-settings');
            return redirect()->to($url);
        }

        admin_toastr(__('Settings updated successfully!'), 'success');
        return redirect()->back();
        //        return redirect($redirectUrl);
    }


    public function updateAppConfig(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }
        $data = $request->except(['_token', 'current_tab', 'inner_tab_type']);

        // App colors/backgrounds are no longer panel-managed (owner decision):
        // they are fixed in the Flutter code per theme. This endpoint now only
        // persists the theme choice (app_ui_variant) and the bottom-nav icons.

        // Nav icons: 5 tabs × (active + inactive).
        //  - A new uploaded file overwrites the key.
        //  - An explicit clear (remove_<key> == 1) stores an empty value so the
        //    API emits '' for that slot and the app reverts to its bundled
        //    default icon. When BOTH states of a tab are cleared, the legacy
        //    single nav_icon_N is also cleared so the API fallback can't revive
        //    the old icon.
        //  - Otherwise the key is left untouched (an empty submit never wipes an
        //    existing icon — explicit-only no-wipe behavior is preserved).
        for ($i = 1; $i <= 5; $i++) {
            $activeKey   = "nav_icon_active_$i";
            $inactiveKey = "nav_icon_inactive_$i";

            $clearActive   = false;
            $clearInactive = false;

            foreach ([$activeKey, $inactiveKey] as $iconKey) {
                $clearFlag = $request->input("remove_$iconKey") == '1';
                if ($request->hasFile($iconKey)) {
                    $data[$iconKey] = Common::upload('images', $request->file($iconKey));
                } elseif ($clearFlag) {
                    $data[$iconKey] = '';
                    if ($iconKey === $activeKey) {
                        $clearActive = true;
                    } else {
                        $clearInactive = true;
                    }
                } else {
                    unset($data[$iconKey]);
                }
            }

            // Drop the legacy single-icon fallback only when neither per-state
            // key keeps a value (both cleared and no new upload for either).
            if (
                $clearActive && $clearInactive
                && !$request->hasFile($activeKey)
                && !$request->hasFile($inactiveKey)
            ) {
                $data["nav_icon_$i"] = '';
            }
        }
        // Strip the loose clear-flag inputs so they are never persisted as keys.
        for ($i = 1; $i <= 5; $i++) {
            unset($data["remove_nav_icon_active_$i"], $data["remove_nav_icon_inactive_$i"]);
        }

        // Always bump so the app detects + applies the change on the FIRST app
        // launch after ANY save on the colors/theme page (not only on reset).
        // The app compares this vs its stored color_time, so a refetch happens
        // exactly once after a save — cache-first is preserved otherwise.
        // colors_updated_at / ground_updated_at are bumped centrally in
        // SettingObserver::saved on the Setting::updateOrCreate calls below, as a
        // proper Unix timestamp (the old per-key settings()->set($cacheKey, true)
        // wrote a boolean the app-check never saw as newer). Removed.
        settings()->set('color_setting_updated_at', Carbon::now()->timestamp);
        // Process and save settings
        foreach ($data as $key => $value) {

            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $value = Common::upload('images', $value);
            }

            // Never persist an empty color: an empty hex breaks color parsing in
            // the app and blanks the whole UI to white. Skip the key so the
            // existing (or brand-default) value is preserved.
            if (Str::contains($key, 'color') && is_string($value) && trim($value) === '') {
                continue;
            }

            if (!is_null($value)) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                // Clear old cache first
                Cache::forget($key);
                Cache::put($key, $value);
            }
        }

        // Targeted invalidation only — never Cache::flush() (see update()). Bust
        // the aggregate settings cache read by the app-check endpoint; per-key
        // caches were already forgotten+re-put in the loop above.
        Cache::forget('all_settings');

        admin_toastr(__('Settings updated successfully!'), 'success');

        $redirectUrl = url(config('admin.route.prefix') . '/settings');

        if ($request->has('current_tab')) {
            $redirectUrl .= '?tab=' . $request->current_tab;
            if ($request->has('inner_tab_type')) {
                $redirectUrl .= '&type=' . $request->inner_tab_type;
            }
        }

        return redirect($redirectUrl);
    }

    public function store_notification_templates(Request $request)
    {

        $validated = $request->validate([
            'key' => 'required|unique:notifications,key',
        ]);

        $template = Notification::create(['key' => $validated['key']]);

        $languages = ['ar', 'en', 'tr', 'hi'];

        foreach ($languages as $code) {
            if ($request->has("title_{$code}") && $request->has("message_{$code}")) {
                NotificationTranslation::updateOrCreate(

                    [
                        'notification_id' => $template->id,
                        'language' => $code
                    ],
                    [
                        'title' => $request->input("title_{$code}"),
                        'message' => $request->input("message_{$code}"),
                    ]

                );
            }
        }



        admin_toastr(__('Settings updated successfully!'), 'success');

        return back();
    }

    public function edit_notification_templates(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-notification');
        }

        $template = Notification::findOrFail($request->id);

        $languages = ['ar', 'en', 'tr', 'hi'];

        foreach ($languages as $code) {
            if ($request->filled("title_{$code}") && $request->filled("message_{$code}")) {
                NotificationTranslation::updateOrCreate(
                    [
                        'notification_id' => $template->id,
                        'language' => $code
                    ],
                    [
                        'title'   => $request->input("title_{$code}"),
                        'message' => $request->input("message_{$code}"),
                    ]
                );
            }
        }

        admin_toastr(__('Settings updated successfully!'), 'success');

        return back();
    }

    /**
     * Persist a game-provider row (per active provider) + bust its cache.
     *
     * Mirrors App\Admin\Controllers\AllGameController@gameSettings so a provider
     * save works even when the card's inputs get folded into the app-settings
     * form (nested-form breakage). Generic over provider_code; only touches the
     * game_provider_settings table and its cache key — never the settings table.
     */
    protected function saveGameProvider(Request $request)
    {
        $request->validate([
            'provider_code'           => 'required|string|max:255',
            'provider_name'           => 'nullable|string|max:255',
            'app_key'                 => 'nullable|string|max:255',
            'app_id'                  => 'nullable|string|max:255',
            'app_secret'              => 'nullable|string|max:255',
            'channel'                 => 'nullable|string|max:255',
            'gsp'                     => 'nullable|string|max:64',
            'base_url'                => 'nullable|string|max:255',
            'callback_url'            => 'nullable|string|max:255',
            'webhook_sign_key_source' => 'nullable|in:app_key,app_id,secret',
            'ip_allowlist'            => 'nullable|string|max:4000',
            'app_game_endpoints'      => 'nullable|string|max:8000',
            'active'                  => 'nullable|boolean',
        ]);

        $appGameEndpoints = null;
        if ($request->filled('app_game_endpoints')) {
            $appGameEndpoints = json_decode($request->app_game_endpoints, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($appGameEndpoints)) {
                return back()->withInput()->withErrors([
                    'app_game_endpoints' => 'Must be a valid JSON object of interface => URL.',
                ]);
            }
        }

        $attributes = [
            'provider_name'           => $request->provider_name,
            'app_key'                 => $request->app_key,
            'app_id'                  => $request->app_id,
            'base_url'                => $request->base_url,
            'callback_url'            => $request->callback_url,
            'webhook_sign_key_source' => $request->webhook_sign_key_source ?: 'app_key',
            'ip_allowlist'            => $request->ip_allowlist,
            'is_active'               => $request->active,
        ];

        if ($request->filled('app_game_endpoints')) {
            $existing = \App\Models\GameProviderSetting::where('provider_code', $request->provider_code)->value('extra_settings');
            $existing = is_string($existing) ? (json_decode($existing, true) ?: []) : (is_array($existing) ? $existing : []);
            $existing['app_game_endpoints'] = $appGameEndpoints;
            $attributes['extra_settings'] = $existing;
        }

        // Only overwrite the encrypted secret when a value is actually submitted,
        // so leaving the masked field blank keeps the stored secret intact.
        if ($request->filled('app_secret')) {
            $attributes['app_secret'] = $request->app_secret;
        }

        // channel/gsp are used only by providers that post them — guard so other provider forms do not null them.
        if ($request->filled('channel')) {
            $attributes['channel'] = $request->channel;
        }
        if ($request->filled('gsp')) {
            $attributes['gsp'] = $request->gsp;
        }

        // ── Schema-adaptive persistence ─────────────────────────────────────
        // Some environments still carry the legacy generic key/value shape of
        // game_provider_settings: `provider`, `key`, `type` are NOT NULL with no
        // default, and base_url/callback_url/extra_settings may not exist yet. A
        // plain create() there throws "Field 'provider' doesn't have a default
        // value" — which silently blocks provider activation. So: (1) drop
        // attributes whose column is absent, and (2) on INSERT satisfy the legacy
        // NOT NULL columns. Activation then works on ANY schema, no migration.
        $table   = (new \App\Models\GameProviderSetting)->getTable();
        $hasCol  = fn (string $c) => \Illuminate\Support\Facades\Schema::hasColumn($table, $c);

        foreach (array_keys($attributes) as $col) {
            if (! $hasCol($col)) {
                unset($attributes[$col]);
            }
        }

        $exists = \App\Models\GameProviderSetting::where('provider_code', $request->provider_code)->exists();
        if (! $exists) {
            if ($hasCol('provider') && ! array_key_exists('provider', $attributes)) {
                $attributes['provider'] = $request->provider_code;
            }
            if ($hasCol('key') && ! array_key_exists('key', $attributes)) {
                $attributes['key'] = $request->provider_code;
            }
            if ($hasCol('type') && ! array_key_exists('type', $attributes)) {
                $attributes['type'] = 'game_provider';
            }
        }

        $gameSetting = \App\Models\GameProviderSetting::updateOrCreate(
            ['provider_code' => $request->provider_code],
            $attributes
        );

        Cache::forget('game_provider_' . $request->provider_code);
        Cache::put('game_provider_' . $request->provider_code, $gameSetting, now()->addHours(2));

        if ($request->has('redirect_to')) {
            return redirect()->to($request->redirect_to);
        }

        $redirectUrl = url(config('admin.route.prefix') . '/settings');
        if ($request->has('current_tab')) {
            $redirectUrl .= '?tab=' . $request->current_tab;
            if ($request->has('inner_tab_type')) {
                $redirectUrl .= '&type=' . $request->inner_tab_type;
            }
        }

        admin_toastr(__('Settings updated successfully!'), 'success');
        return redirect($redirectUrl);
    }

    public function checkActiveTargets(Request $request)
    {
        $target = Target::first();
        $hasActiveTargets = false;

        if ($target) {
            $users = MonthlyDiamondReceive::where('monthly_diamond_received', '>=', $target->diamonds)->where('month', now()->month)->where('year', now()->year)->get();
            $hasActiveTargets = $users->count() > 0;
        }

        return response()->json(['hasActiveTargets' => $hasActiveTargets]);
    }
}
