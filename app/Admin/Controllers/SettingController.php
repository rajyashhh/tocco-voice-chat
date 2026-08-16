<?php

namespace App\Admin\Controllers;

use App\Admin\Controllers\MainController;
use App\Helpers\Common;
use App\Models\BrandImage;
use App\Models\Country;
use App\Models\GameProviderSetting;
use App\Models\Language;
use App\Models\PaymentCoin;
use App\Models\Setting;
use App\Models\Timezone;
use App\Rules\HexColor;
use Cache;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class SettingController extends MainController
{
    protected $title = 'Settings';
    public $permission_name = 'settings';

    public function index(Content $content)
    {
        // Batch load all settings + configs in 2 queries instead of 20+ individual calls
        $settings = Setting::pluck('value', 'key')->toArray();
        $configs = \App\Models\Config::pluck('value', 'name')->toArray();

        // Helper to read from pre-loaded configs
        $cfg = fn(string $key) => $configs[$key] ?? null;

        $timezones = Timezone::all();
        $soundLibrary = $cfg('sound_library');
        $videoLibrary = $cfg('video_library');
        $liveLibrary = $cfg('live_library');
        $paymentCoins = PaymentCoin::with('settings')->uniqueTypes()->orderByDesc('status')->get();
        $is_auto_preview = $cfg('is_auto_preview');
        $countries = Country::select(['id', 'name', 'e_name'])->get();
        $languages = Language::select(['id', 'name', 'code'])->get();
        $chargeTabType = request()->get('type', 'Experience');
        $utd_stream_app_id = $cfg('utd_stream_app_id');
        $utd_stream_server_secret = $cfg('utd_stream_server_secret');
        $utd_stream_callback_secret = $cfg('utd_stream_callback_secret');
        $utd_stream_app_key = $cfg('utd_stream_app_key');
        // UTD Games (leader-cc aggregator, own app_key) card on the games tab.
        $utdSettings = GameProviderSetting::where('provider_code', 'utd')->first();
        $isThemeEnabled = $settings['isThemeEnabled'] ?? 0;

        return parent::index($content
            ->header(__('Settings'))
            ->description('   ')
            ->body(view('admin.settings_new', compact([
                'isThemeEnabled',
                'utdSettings',
                'chargeTabType',
                'settings',
                'languages',
                'timezones',
                'paymentCoins',
                'soundLibrary',
                'videoLibrary',
                'liveLibrary',
                'is_auto_preview',
                'countries',
                'utd_stream_app_id',
                'utd_stream_server_secret',
                'utd_stream_callback_secret',
                'utd_stream_app_key'
            ]))));
    }

    /**
     * Third Party services page — its own menu item, separate from the tabbed
     * Settings page. The third_party partial reads its own service values via
     * Common::getSettingValue() internally; we only need to supply the vars the
     * embedded UTD Stream & Games partials expect (utd_stream_* and $utdSettings)
     * plus $settings for the shared css/js partials.
     */
    public function thirdParty(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $settings = Setting::pluck('value', 'key')->toArray();

        $utd_stream_app_id        = Common::getConfig('utd_stream_app_id');
        $utd_stream_server_secret = Common::getConfig('utd_stream_server_secret');
        $utd_stream_callback_secret = Common::getConfig('utd_stream_callback_secret');
        $utd_stream_app_key       = Common::getConfig('utd_stream_app_key');

        // The embedded UTD Stream partial reads the media-provider selectors
        // (sound/video/live library + auto-preview) — same vars index() supplies.
        $soundLibrary    = Common::getConfig('sound_library');
        $videoLibrary    = Common::getConfig('video_library');
        $liveLibrary     = Common::getConfig('live_library');
        $is_auto_preview = Common::getConfig('is_auto_preview');

        // UTD Games (leader-cc aggregator, own app_key) card on the Games tab.
        $utdSettings = GameProviderSetting::where('provider_code', 'utd')->first();

        return parent::index($content
            ->header(__('Third Party Settings'))
            ->description('   ')
            ->body(view('admin.settings.third_party_page', compact(
                'settings',
                'utdSettings',
                'utd_stream_app_id',
                'utd_stream_server_secret',
                'utd_stream_callback_secret',
                'utd_stream_app_key',
                'soundLibrary',
                'videoLibrary',
                'liveLibrary',
                'is_auto_preview'
            ))));
    }

    public function save_image(Request $request)
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        $name = Common::upload('images', $request->image);
        BrandImage::create([
            'name' => $name
        ]);
        return Common::apiResponse(true, 'Success');
    }

    public function saveSettings(Request $request)
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        $data = $request->except(['_token', 'current_tab', 'inner_tab_type']);

        // Block invalid hex colors at the source (theme color keys), so the
        // mobile app never receives a value that crashes it at build time.
        $colorRules = [];
        foreach (array_keys($data) as $key) {
            if (str_ends_with($key, '_color') || preg_match('/^gradient_\d+$/', $key)) {
                $colorRules[$key] = ['nullable', new HexColor()];
            }
        }
        if (!empty($colorRules)) {
            $request->validate($colorRules);
        }

        try {
            foreach ($data as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value]);
                Cache::forget($key);
                Cache::put($key, $value, now()->addYear());
            }

            // The app-check endpoint (VersionController::getSettingsArray) reads
            // the aggregate 'all_settings' cache, which is rememberForever. Per-key
            // forgets above never touch it, so a saved value (e.g. app_ui_variant)
            // never reached the app until this cache was rebuilt. Bust it here so
            // the next app-check rebuilds it with the freshly saved values.
            Cache::forget('all_settings');

            $redirectUrl = url(config('admin.route.prefix') . '/settings');

            if ($request->has('current_tab')) {
                $redirectUrl .= '?tab=' . $request->current_tab;
                if ($request->has('inner_tab_type')) {
                    $redirectUrl .= '&type=' . $request->inner_tab_type;
                }
            }

            admin_success(__('Success'), __('Settings updated successfully!'));
            return redirect($redirectUrl);
        } catch (Exception $exception) {
            return back()->with('error', $exception->getMessage());
        }
    }


    public function updateRoomCup(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'room_cup'],
                ['value' => $request->value]
            );

            Cache::forget('room_cup');
            Cache::put('room_cup', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateRoomBoom(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'room_boom'],
                ['value' => $request->value]
            );

            Cache::forget('room_boom');
            Cache::put('room_boom', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateRemainingDiamonds(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'remaining_diamonds_action'],
                ['value' => $request->value]
            );

            Cache::forget('remaining_diamonds_action');
            Cache::put('remaining_diamonds_action', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateHostLevel(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'host_level_action'],
                ['value' => $request->value]
            );

            Cache::forget('host_level_action');
            Cache::put('host_level_action', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function updatePkLive(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'pk_live_action'],
                ['value' => $request->value]
            );

            Cache::forget('pk_live_action');
            Cache::put('pk_live_action', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateLuckyGifts(Request $request): JsonResponse
    {
        try {
            Setting::updateOrCreate(
                ['key' => 'lucky_gifts_action'],
                ['value' => $request->value]
            );

            Cache::forget('lucky_gifts_action');
            Cache::put('lucky_gifts_action', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function updateIsThemeEnabled(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'isThemeEnabled'],
                ['value' => $request->value]
            );

            Cache::forget('isThemeEnabled');
            Cache::put('isThemeEnabled', $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function updateRoomMode(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => $request->field],
                ['value' => $request->value]
            );

            Cache::forget($request->field);
            Cache::put($request->field, $request->value, now()->addYear());

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }

    public function updateCharismaFormat(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'charisma_format'],
                ['value' => $request->value]
            );

            Cache::forget('charisma_format');
            Cache::put('charisma_format', $request->value, now()->addYear());

            Config::set('charisma.format', (bool) $request->value);

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }


    public function updateCharismaBadge(Request $request): JsonResponse
    {
        if (!Admin::user()->can('*')) Permission::check('edit-' . $this->permission_name);

        try {
            Setting::updateOrCreate(
                ['key' => 'charisma_badge'],
                ['value' => $request->value]
            );

            Cache::forget('charisma_badge');
            Cache::put('charisma_badge', $request->value, now()->addYear());

            Config::set('charisma.badge', (bool) $request->value);

            return Common::apiResponse(true, 'created successfully');
        } catch (Exception $exception) {
            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
    }
}
