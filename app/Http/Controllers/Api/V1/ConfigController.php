<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConfigCategory;
use App\Http\Requests\Api\ConfigValuesRequest;
use App\Models\Config;
use App\Helpers\Common;
use App\Models\Pack;
use Illuminate\Http\Request;
use App\Services\ConfigService;
use Doctrine\DBAL\Schema\Index;
use App\Http\Controllers\Controller;
use App\Tik\Services\CountryService;
use App\Http\Resources\CountryResource;
use App\Http\Resources\Api\V1\ConfigResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config as LaravelConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;

class ConfigController extends Controller
{

    protected $configService;
    public $permission_name = 'agency-settings';
    public $permission_config_name = 'update_setting_button';

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function uploadBadges(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        foreach ($request->allFiles() as $input => $file) {

            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    $value = Common::upload('images', $singleFile);
                    Config::updateOrCreate(['name' => $input], [
                        'value' => $value
                    ]);
                }
            } else {
                $value = Common::upload('images', $file);
                Config::updateOrCreate(['name' => $input], [
                    'value' => $value
                ]);
            }
        }
        settings()->set('badges_agency_update_at', time());
        return back();
    }

    public function getConfigValues(ConfigValuesRequest $request)
    {
        $configs = [];
        if (isset($request['keys'])) {
            $keys = $request['keys'];
            $keys = array_diff($keys, ['zego_server_secret', 'zego_app_id', 'app_sign']);
            
            $sorted = $keys;
            sort($sorted);
            $cacheKey = 'config_keys_' . md5(json_encode($sorted));
            $configs = Cache::remember($cacheKey, 300, function () use ($keys) {
                return Common::getConfFromKey($keys);
            });

            $configs = $configs->flatMap(function ($value) {
                return [
                    $value->name => $value->value,
                ];
            });
        }
        if ($request['enable-special'] == 1) {
            $wapel                 = Pack::query()
                ->where('type', 12)
                ->where('expire', '>=', time())
                ->where('user_id', \Auth::id())
                ->where('use_num', '>', 0)
                ->select(['id', 'use_num'])
                ->first();
            $configs['wapel_num']  =  @(int)$wapel->use_num ?? 0;
            $user                  = $request->user();
            $configs['user_coins'] =  @(int)$user->di ?? 0;
            $configs['user_coins_string'] =  @$user->coins_string ?? '0';
        }
        return Common::apiResponse(true, 'config returned success', $configs, 200);
    }



    public function index()
    {
        $data = $this->configService->getAllConfigs();
        return Common::apiResponse(1, '', $data);
    }

    /**
     * Read a Firebase Setting value, falling back to $default (or '') when the
     * stored value is null/empty. Cached 300s under the bare key (the same key
     * SettingObserver flushes on save) — mirrors ColorController::colorSetting.
     */
    private function firebaseSetting(string $key, string $default = ''): string
    {
        $value = Cache::remember($key, 300, function () use ($key) {
            return \App\Models\Setting::where('key', $key)->value('value');
        });

        return ($value !== null && trim((string) $value) !== '') ? (string) $value : $default;
    }

    /**
     * Public app-facing Firebase client config (Crashlytics / Auth / Firestore).
     * Per-platform identity read from the SETTINGS table so a white-label clone
     * configures its own Firebase from the panel. android.apiKey / iOS bucket etc.
     * fall back to the existing firebase_web_api_key / firebase_auth_domain where
     * sensible so a setup that only filled the SMS card still returns a usable key.
     */
    public function firebaseConfig()
    {
        $data = Cache::remember('firebase_config', 300, function () {
            $webApiKey  = $this->firebaseSetting('firebase_web_api_key');
            $authDomain = $this->firebaseSetting('firebase_auth_domain');
            // One Firebase project backs all platforms: senderId/projectId are
            // shared, so web/iOS reuse the android values when their own field is
            // blank. databaseURL is the single admin Realtime DB key, emitted on
            // every section (the Dart store reads it per-section). storageBucket
            // falls back to a shared bucket so a setup that only filled the SMS
            // card still gets Storage instead of '' (which the store nulls out).
            $databaseUrl   = $this->firebaseSetting('firebase_database_uri');
            $sharedSender  = $this->firebaseSetting('firebase_app_android_sender_id');
            $sharedProject = $this->firebaseSetting('firebase_app_android_project_id');
            $sharedBucket  = $this->firebaseSetting('firebase_app_android_bucket')
                ?: $this->firebaseSetting('gcs_bucket');

            $androidBucket = $this->firebaseSetting('firebase_app_android_bucket', $sharedBucket);
            $iosBucket     = $this->firebaseSetting('firebase_app_ios_bucket', $sharedBucket);
            $webBucket     = $this->firebaseSetting('firebase_app_web_bucket', $sharedBucket);

            return [
                'android' => [
                    'apiKey'            => $this->firebaseSetting('firebase_app_android_api_key', $webApiKey),
                    'appId'             => $this->firebaseSetting('firebase_app_android_app_id'),
                    'messagingSenderId' => $sharedSender,
                    'projectId'         => $sharedProject,
                    'storageBucket'     => $androidBucket,
                    'databaseURL'       => $databaseUrl,
                ],
                'ios' => [
                    'apiKey'            => $this->firebaseSetting('firebase_app_ios_api_key', $webApiKey),
                    'appId'             => $this->firebaseSetting('firebase_app_ios_app_id'),
                    'messagingSenderId' => $this->firebaseSetting('firebase_app_ios_sender_id', $sharedSender),
                    'projectId'         => $this->firebaseSetting('firebase_app_ios_project_id', $sharedProject),
                    'storageBucket'     => $iosBucket,
                    'databaseURL'       => $databaseUrl,
                ],
                // Web section (kIsWeb path in the Dart store). Reuses the shared
                // project's senderId/projectId; a clone only needs to fill the
                // web apiKey + appId (its own Firebase web app) from the panel.
                'web' => [
                    'apiKey'            => $this->firebaseSetting('firebase_app_web_api_key', $webApiKey),
                    'appId'             => $this->firebaseSetting('firebase_app_web_app_id'),
                    'messagingSenderId' => $this->firebaseSetting('firebase_app_web_sender_id', $sharedSender),
                    'projectId'         => $this->firebaseSetting('firebase_app_web_project_id', $sharedProject),
                    'storageBucket'     => $webBucket,
                    'databaseURL'       => $databaseUrl,
                    'authDomain'        => $authDomain,
                    'measurementId'     => $this->firebaseSetting('firebase_app_web_measurement_id'),
                ],
                'phoneAuthEnabled' => (string) $this->firebaseSetting('firebase_phone_auth_enabled') === '1',
                // Active phone OTP provider (firebase|twilio|whatsapp) so the
                // app renders the right flow: Firebase SDK round-trip vs the
                // server-code screen (POST auth/send-otp + code field).
                'phoneOtpProvider' => in_array($this->firebaseSetting('phone_otp_provider'), ['twilio', 'whatsapp'], true)
                    ? $this->firebaseSetting('phone_otp_provider')
                    : 'firebase',
                'authDomain'       => $authDomain,
                'databaseURL'      => $databaseUrl,
                // Admin-set Google OAuth web client id (third-party settings).
                // Pre-auth so the login screen's GoogleSignIn factory can prefer
                // the panel value over the baked google-services.json default.
                'googleClientId'   => $this->firebaseSetting('google_client_id'),
            ];
        });

        return Common::apiResponse(true, '', $data, 200);
    }

    public function updateConfig(Request $request)
    {
        Config::find($request->config_id)->update([
            "value" => $request->value,
        ]);
        return Common::apiResponse(1, 'updated successfully');
    }

    public function config(Request $request)
    {
        $configs = Config::where('is_hidden', 0)->whereNotNull("category")->get()->groupBy('category');
        $formattedConfigs = [];

        foreach ($configs as $category => $items) {
            $formattedConfigs[__($category)]['sub_category'] = ConfigCategory::getLinkedStringsByValue($category);
            $formattedConfigs[__($category)]['data'] = ConfigResource::collection($items);
        }


        return Common::apiResponse(1, '', $formattedConfigs);
    }

    public function updateConfigChatGroup(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_config_name);
        }

        $config = Config::find($request->id);
        $config->value = $request->value;
        $config->save();
        return Redirect::back();
    }


    public function UpdateConfigsGroupChat(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_config_name);
        }

        foreach ($request->except('_token') as $key => $value) {
            if (!is_null($value)) {
                Config::updateOrCreate(['name' => $key], ['value' => $value]);

                Cache::forget($key);
                Cache::forever($key, $value);
            }
        }

        // Invalidate only the config-related caches (not the whole store)
        Cache::forget('all_configs');
        Cache::forget('pusher_config');

        // Re-prime all_configs from DB so the redirected page has fresh data
        \App\Helpers\CacheHelper::cacheConfig();

        admin_success('Saved Successfully');
        return Redirect::back();
    }

    public function updateConfigAgoraZego(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-settings');
        }

        $excludeKeys = ['_token', 'redirect_to', 'current_tab', 'inner_tab_type'];
        $keys = array_diff(array_keys($request->all()), $excludeKeys);
        $updatedKeys = [];

        // White-label credential keys the backend reads from the SETTINGS table
        // (not the config table): firebaseCredentials() + setupStorageCredentials()
        // + VersionController storage_url. The third-party settings page writes them
        // here so a clone configures everything from the panel (no server file upload).
        $settingsKeys = ['firebase_service_account_json', 'gcs_service_account_json', 'gcs_bucket', 'gcs_project_id', 'firebase_project_name', 'storage_url', 'firebase_phone_auth_enabled', 'firebase_web_api_key', 'firebase_auth_domain', 'centrifugo_api_url', 'centrifugo_ws', 'centrifugo_api_key', 'centrifugo_hmac_secret', 'centrifugo_proxy_secret',
            // Firebase APP identity (Crashlytics / Auth / Firestore) — per-platform
            // client config the app reads from /firebase-config. Plain identifiers,
            // not server secrets, so they are NOT in $secretKeys (blank submit
            // overwrites with blank like the other endpoint fields).
            'firebase_app_android_api_key', 'firebase_app_android_app_id', 'firebase_app_android_sender_id', 'firebase_app_android_project_id', 'firebase_app_android_bucket',
            'firebase_app_ios_api_key', 'firebase_app_ios_app_id', 'firebase_app_ios_sender_id', 'firebase_app_ios_project_id', 'firebase_app_ios_bucket',
            // Firebase WEB app identity + Realtime Database URI (served by
            // /firebase-config). Same plain-identifier rule: a blank submit
            // overwrites with blank, never kept secret.
            'firebase_database_uri',
            'firebase_app_web_api_key', 'firebase_app_web_app_id', 'firebase_app_web_sender_id', 'firebase_app_web_project_id', 'firebase_app_web_bucket', 'firebase_app_web_measurement_id',
            // YouTube Data API v3 key — served to the app in the app-check payload
            // next to youtube_status. Secret: a blank submit keeps the current value.
            'youtube_api_key',
            // Google OAuth client id (Sign-in audience) — plain identifier read
            // DB-first by AuthService via Common::whiteLabel; seeded blank by
            // WhiteLabelSettingsSeeder and flushed by SettingObserver.
            'google_client_id',
            // Phone OTP: active provider switch (firebase|twilio|whatsapp, read
            // by OtpProviderService) + Twilio SMS credentials (TwilioSms).
            'phone_otp_provider', 'twilio_account_sid', 'twilio_auth_token', 'twilio_from_number'];
        // Secrets are NOT pre-filled in the form: a blank submit means "keep current".
        $secretKeys = ['firebase_service_account_json', 'gcs_service_account_json', 'centrifugo_api_key', 'centrifugo_hmac_secret', 'centrifugo_proxy_secret', 'youtube_api_key', 'twilio_account_sid', 'twilio_auth_token'];

        foreach ($keys as $key) {
            // Optional fields arrive as null (ConvertEmptyStringsToNull), but
            // configs.value is NOT NULL under MySQL strict mode — a raw null
            // would abort the save mid-loop, leaving the form half-written.
            $value = $request->input($key) ?? '';

            if (in_array($key, $settingsKeys, true)) {
                if (in_array($key, $secretKeys, true) && trim((string) $value) === '') {
                    continue; // keep the existing secret
                }
                \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
                Cache::forget($key);
                Cache::put($key, (string) $value, now()->addYear());
                $updatedKeys[] = $key;
                continue;
            }

            // Log if live_library is being updated via this route (should NOT happen)
            if ($key === 'live_library') {
                Log::warning('Live Library Config - WRONG ROUTE! live_library being updated via updateConfigAgoraZego', [
                    'value' => $value,
                    'all_request' => $request->all(),
                    'url' => $request->fullUrl(),
                    'referer' => $request->header('referer'),
                    'is_ajax' => $request->ajax(),
                ]);
            }

            Config::updateOrCreate(
                ['name' => $key],
                ['value' => $value]
            );

            $updatedKeys[] = $key;
            Cache::forget($key);
        }

        Cache::forget('all_configs');

        // Re-cache all_configs immediately from DB so the redirected page has fresh data
        // This is critical for Octane: Common::getConfig() uses Cache::get('all_configs')
        // which does NOT auto-repopulate like rememberForever does.
        \App\Helpers\CacheHelper::cacheConfig();

        if (method_exists(Cache::store('octane'), 'flush')) {
            Cache::store('octane')->flush();
        }

        $redirectUrl = url('admin/settings');

        if ($request->has('current_tab')) {
            $redirectUrl .= '?tab=' . $request->current_tab;
            if ($request->has('inner_tab_type')) {
                $redirectUrl .= '&type=' . $request->inner_tab_type;
            }
        } elseif ($request->has('redirect_to')) {
            return Redirect::to($request->redirect_to);
        }

        return redirect($redirectUrl);
    }

  /*  public function updateConfigAgoraZego(Request $request)
    {
        $inputValue = $request->input('live_library');


        // Update directly via DB to avoid observer re-caching with stale data
        $exists = \DB::table('configs')->where('name', 'live_library')->exists();
        if ($exists) {
            \DB::table('configs')->where('name', 'live_library')->update([
                'value' => $inputValue,
                'updated_at' => now(),
            ]);
        } else {
            \DB::table('configs')->insert([
                'name' => 'live_library',
                'value' => $inputValue,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Confirm the update in DB
        $afterValue = \DB::table('configs')->where('name', 'live_library')->value('value');

        // Now clear ALL caches and re-cache with fresh data
        Cache::forget('live_library');
        Cache::forget('all_configs');

        try {
            Cache::flush();
        } catch (\Exception $e) {
            Log::error('Live Library - Cache flush failed', ['error' => $e->getMessage()]);
        }

        // Re-cache all_configs with fresh data from DB
        $freshConfigs = \DB::table('configs')->pluck('value', 'name')->toArray();
        Cache::forever('all_configs', $freshConfigs);


        try {
            if (method_exists(Cache::store('octane'), 'flush')) {
                Cache::store('octane')->flush();
            }
        } catch (\Exception $e) {
            Log::error('Live Library - Octane cache flush failed', ['error' => $e->getMessage()]);
        }

        if ($hasPusherUpdate) {
            $pusherMapping = [
                'pusher_app_key' => 'broadcasting.connections.pusher.key',
                'pusher_app_secret' => 'broadcasting.connections.pusher.secret',
                'pusher_app_id' => 'broadcasting.connections.pusher.app_id',
                'pusher_app_cluster' => 'broadcasting.connections.pusher.options.cluster',
            ];

            foreach ($pusherMapping as $key => $configKey) {
                $value = $request->input($key);
                if ($value !== null) {
                    if ($key === 'pusher_app_cluster') {
                        LaravelConfig::set($configKey, $value ?? 'mt1');
                    } else {
                        LaravelConfig::set($configKey, $value);
                    }
                }
            }

            \App\Services\OctaneBroadcasterService::rebuildBroadcaster();

        }

        $redirectUrl = url('admin/settings?tab=realTimeSetting');
        return redirect($redirectUrl);
    }*/
}
