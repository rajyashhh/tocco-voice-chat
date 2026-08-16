<?php

namespace App\Providers;

use App\Admin\Fields\Image;
use App\Admin\Fields\ImagePath;
use App\Classes\UserHandling;
use App\Helpers\CacheHelper;
use App\Helpers\Common;
use App\Helpers\CustomNotification;
use App\Helpers\ManagerHelper;
use App\Helpers\RoomHelper;
use App\Models\Agency;
use App\Models\AgencyJoinRequest;
use App\Models\Bd;
use App\Models\CoinGameUser;
use App\Models\Emoji;
use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\Gift;
use App\Models\Language;
use App\Models\Pk;
use App\Models\Room;
use App\Models\Setting;
use App\Models\ShippingAgency;
use App\Models\User;
use App\Models\UserSallary;
use App\Models\Ware;
use App\Observers\AgencyJoinRequestObserver;
use App\Observers\AgencyObserver;
use App\Observers\AreaManagerObserver;
use App\Observers\BdObserver;
use App\Observers\CoinGameUserObserver;
use App\Observers\ConfigObserver;
use App\Observers\EmojiObserver;
use App\Observers\FamilyObserver;
use App\Observers\FamilyUserObserver;
use App\Observers\GiftObserver;
use App\Observers\PersonalAccessTokenObserver;
use App\Observers\PKObserver;
use App\Observers\RoomBoomLevelObserver;
use App\Observers\RoomObserver;
use App\Observers\SettingObserver;
use App\Observers\ShippingAgencyObserver;
use App\Observers\SuperAdminObserver;
use App\Observers\UserObserver;
use App\Observers\UserSallaryObserver;
use App\Observers\VipObserver;
use App\Observers\WareObserver;
use App\Repositories\Community\SearchRepository;
use App\Repositories\Community\SearchRepositoryInterface;
use App\Repositories\Room\RoomRepo;
use App\Repositories\Room\RoomRepoInterface;
use App\Repositories\User\UserRepo;
use App\Repositories\User\UserRepoInterface;
use App\Services\RedisService;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Region\Entities\AreaManager;
use Modules\RoomBoom\Entities\RoomBoomLevel;
use Modules\Country\Entities\SuperAdmin;
use Modules\Vip\Entities\Vip;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        URL::forceScheme('https');

        // Octane safety: an 'array' cache driver is per-worker (NOT shared), so
        // cached settings/colors go stale across workers and admin-panel changes
        // stop propagating (e.g. text colors stuck on an old value for requests
        // served by other workers). If something forced 'array' but redis is
        // configured, prefer redis so the cache is cluster-consistent and panel
        // edits apply everywhere. Falls back to the configured driver when redis
        // is absent, so a deploy without redis is unaffected.
        if (config('cache.default') === 'array' && config('database.redis.default.host')) {
            config(['cache.default' => 'redis']);
        }

        Form::extend('image', Image::class);
        Form::extend('imagePath', ImagePath::class);

        if ($this->app->isLocal()) {
            //            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        $this->app->bind(RoomRepoInterface::class, RoomRepo::class);
        $this->app->bind(UserRepoInterface::class, UserRepo::class);
        $this->app->bind('RedisService', fn($app) => new RedisService());
        $this->app->bind('UserHandling', fn($app) => new UserHandling());
        $this->app->bind('CustomNotification', fn($app) => new CustomNotification());
        $this->app->bind('RoomHelper', fn($app) => new RoomHelper());
        $this->app->bind('ManagerHelper', fn($app) => new ManagerHelper());
        $this->app->bind(SearchRepositoryInterface::class, SearchRepository::class);

        // Request-scoped AppSetting: settings.json is read once per request (the
        // app setting resource calls settings() ~6x), and Octane resets scoped
        // instances between requests so runtime toggles never go stale. NOT a
        // worker-persistent singleton/static — that would cache stale flags.
        $this->app->scoped(\App\Classes\AppSetting::class, fn() => new \App\Classes\AppSetting());

        // Register FairLuck V7 engine bindings (live lucky-gift engine)
        $this->registerFairLuckService();

        // Register custom event dispatcher for Octane broadcaster refresh
        if (\App\Services\OctaneBroadcasterService::isOctane()) {
            $this->app->singleton('events', \App\Services\OctaneEventDispatcher::class);
        }

        $this->defineCarbonMacros();
    }

    public function boot(): void
    {
        // Detect N+1 queries: log violations instead of throwing exceptions
        \Illuminate\Database\Eloquent\Model::preventLazyLoading(!$this->app->isProduction());

        // In non-production, log lazy loading violations instead of crashing
        if (!$this->app->isProduction()) {
            \Illuminate\Database\Eloquent\Model::handleLazyLoadingViolationUsing(function ($model, $relation) {
                \Illuminate\Support\Facades\Log::warning("N+1 Query Detected: Lazy loading [{$relation}] on model [" . get_class($model) . "]");
            });
        }

        // Override admin.pjax middleware for Swoole/Octane compatibility
        $this->overridePjaxMiddleware();

        // Enforce the central RBAC guard on EVERY admin route in one place. Module
        // route files (HostLevel/CP/Events/Achievement/Reels/...) register admin
        // routes under the laravel-admin 'admin' middleware group; appending the
        // guard to that group covers them all, so view-only/owner/escalation
        // enforcement can't be dodged via an un-listed module group. Deferred to
        // booted() so the package's 'admin' group is already registered.
        $this->app->booted(function () {
            $this->app['router']->pushMiddlewareToGroup('admin', \App\Http\Middleware\AdminRbacGuard::class);
        });


        $this->dashboardAdminConfig();
        $this->registerModelObservers();

        // Cold-boot safety: on a fresh database (before migrations run) the
        // settings/languages/gift tables don't exist yet, so these DB-backed
        // setup calls throw during artisan bootstrap (migrate, package:discover)
        // and make a clean install impossible. Guard them behind the settings
        // table so the app can bootstrap far enough to migrate, then behaves
        // normally once the schema exists.
        //
        // Hermetic build: Schema::hasTable() itself opens a DB connection, which
        // is refused during an isolated image build (docker build has no MySQL).
        // Catch that so `composer dump-autoload`/package:discover/view:cache can
        // boot the app with no services up. At runtime the stack is always
        // present, so this resolves to the normal hasTable() result.
        $settingsTableReady = false;
        try {
            $settingsTableReady = Schema::hasTable('settings');
        } catch (\Throwable $e) {
            $settingsTableReady = false;
        }
        if ($settingsTableReady) {
            $this->setupAppSettings();
            $this->setupStorageCredentials();
            $this->setupS3StorageCredentials();
            $this->setupRealtimeEndpoint();
            $this->setupPaymentGatewaySecrets();
            $this->setupLanguages();
            $this->cacheLuckyGiftProbabilities();

            // Load your custom settings
            $start = Common::getSettingValue('week_start') ?? 'monday';
            $end = Common::getSettingValue('week_end') ?? 'sunday';

            Carbon::setWeekEndsAt(constant('Carbon\\Carbon::' . strtoupper($end)));
        }
        if (!Str::hasMacro('unescape')) {
            Str::macro('unescape', function ($value) {
                return htmlspecialchars_decode($value, ENT_QUOTES);
            });
        }

        // Support old namespace for PkSessionJob
        if (!class_exists('Utd\TaskStream\Jobs\PkSessionJob') && class_exists('Modules\TaskStream\Jobs\PkSessionJob')) {
            class_alias('Modules\TaskStream\Jobs\PkSessionJob', 'Utd\TaskStream\Jobs\PkSessionJob');
        }
    }

    public function dashboardAdminConfig(): void
    {
        $prefix = request()->segment(1);

        $originalConfig = config('admin.route');

        if ($prefix === 'superadmin') {
            config(['admin.route' => config('admin.superadmin_route')]);
            Admin::routes();
        } elseif ($prefix === 'areaManager') {
            config(['admin.route' => config('admin.area_manager_route')]);
            Admin::routes();
        } elseif ($prefix === 'admin') {
            // Admin::routes();
            config(['admin.route' => $originalConfig]);
        }
    }

    protected function defineCarbonMacros(): void
    {
        Carbon::macro('startAndEndOfMonthUTC', function ($year, $month, $timezone = 'UTC') {
            $firstDay = Carbon::create($year, $month, 1, 0, 0, 0, $timezone);

            return [
                $firstDay->copy()->setTimezone('UTC'),
                $firstDay->copy()->endOfMonth()->setTimezone('UTC'),
            ];
        });
    }

    protected function setupAppSettings(): void
    {

        $locale = app()->getLocale();
        $key = $locale === 'ar' ? 'app_title_ar' : 'app_title_en';

        $appName = Cache::rememberForever("settings.{$key}", function () use ($key) {
            return Setting::where('key', $key)->value('value') ?? 'Default';
        });

        config(['app.name' => $appName]);

        $settings = CacheHelper::cacheSettings();

        /** @var Collection $rememberForever*/
        if (gettype($settings) !== 'array') {
            $settings = $settings->pluck('value', 'key')->toArray();
        }

        Config::set([
            'charisma.format' => (bool) ($settings['charisma_format'] ?? false),
            'charisma.badge' => (bool) ($settings['charisma_badge'] ?? false),
            // themes.* is fixed in config/themes.php (owner decision: panel
            // colors are no longer admin-configurable) — no runtime override.

            'services.fawry' => [
                'fawry_secret' => $settings['fawry_secret'] ?? '',
                'fawry_merchant_code' => $settings['fawry_merchant_code'] ?? '',
                'fawry_return_url' => $settings['fawry_return_url'] ?? '',
                'fawry_url' => $settings['fawry_url'] ?? '',
                'fawry_webhook_url' => $settings['fawry_webhook_url'] ?? '',
            ],

            'services.utd_fawry' => [
                'utd_fawry_secret' => $settings['utd_fawry_secret'] ?? '',
                'utd_fawry_merchant_code' => $settings['utd_fawry_merchant_code'] ?? '',
                'utd_url' => $settings['utd_url'] ?? '',
                'utd_fawry_return_url' => $settings['utd_fawry_return_url'] ?? '',
                'utd_fawry_url' => $settings['utd_fawry_url'] ?? '',
            ],

            'services.utd_paymob' => [
                'utd_paymob_secret' => $settings['utd_paymob_secret'] ?? '',
                'utd_paymob_merchant_code' => $settings['utd_paymob_merchant_code'] ?? '',
                'utd_url' => $settings['utd_paymob_url'] ?? '',
                'utd_paymob_return_url' => $settings['utd_paymob_return_url'] ?? '',
                'utd_paymob_url' => $settings['utd_paymob_url'] ?? '',
            ],

            'paysky' => [
                'api_key' => $settings['paysky_api_key'] ?? '',
                'merchant_id' => $settings['paysky_merchant_id'] ?? '',
                'terminal_id' => $settings['paysky_terminal_id'] ?? '',
                'base_url' => $settings['paysky_base_url'] ?? '',
            ],

            'stripe' => [
                'test_secret_key' => $settings['stripe_test_secret_key'] ?? '',
                'success_url' => $settings['stripe_success_url'] ?? '',
                'cancel_url' => $settings['stripe_cancel_url'] ?? '',
                'currency' => $settings['stripe_currency'] ?? '',
                'webhook_secret' => $settings['stripe_webhook_secret'] ?? '',
                'webhook_url' => $settings['stripe_webhook_url'] ?? '',
            ],

            'nafezly-payments' => [
                'OPAY_CURRENCY' => $settings['opay_currency'] ?? '',
                'OPAY_SECRET_KEY' => $settings['opay_secret_key'] ?? '',
                'OPAY_PUBLIC_KEY' => $settings['opay_public_key'] ?? '',
                'OPAY_MERCHANT_ID' => $settings['opay_merchant_id'] ?? '',
                'OPAY_COUNTRY_CODE' => $settings['opay_country_code'] ?? '',
                'OPAY_BASE_URL' => $settings['opay_base_url'] ?? '',
                'OPAY_WEBHOOK_URL' => $settings['opay_webhook_url'] ?? '',
            ],

            'apple' => [
                'apple_team_id' => $settings['apple_team_id'] ?? '',
                'apple_key_id' => $settings['apple_key_id'] ?? '',
                'apple_client_id' => $settings['apple_client_id'] ?? '',
                'apple_redirect_uri' => $settings['apple_redirect_uri'] ?? '',
                'apple_service_file' => $settings['apple_service_file'] ?? '',
            ],

            'paypal' => [
                'base_url' => $settings['paypal_base_url'] ?? '',
                'client_id' => $settings['paypal_client_id'] ?? '',
                'client_secret' => $settings['paypal_client_secret'] ?? '',
                'currency' => $settings['paypal_currency'] ?? '',
                'webhook_id' => $settings['paypal_webhook_id'] ?? '',
            ],

            'codapay' => [
                'base_url' => $settings['codapay_base_url'] ?? '',
                'api_key' => $settings['codapay_api_key'] ?? '',
                'project_id' => $settings['codapay_project_id'] ?? '',
                'country' => $settings['codapay_country'] ?? '',
                'pay_type' => $settings['codapay_pay_type'] ?? '',
                'currency' => $settings['codapay_currency'] ?? '',
            ],

            // NowPayments IPN secret used to verify the x-nowpayments-sig HMAC on
            // the payment callback. Leaf key only, so the env-based api_key/callback_url
            // siblings under services.now_payments are preserved.
            'services.now_payments.ipn_secret' => $settings['nowpayments_ipn_secret']
                ?? config('services.now_payments.ipn_secret'),

            'utd' => [
                'base_url' => $settings['utd_base_url'] ?? '',
                'api_key' => $settings['utd_api_key'] ?? '',
                'project_id' => $settings['utd_project_id'] ?? '',
                'webhook_secret' => $settings['utd_webhook_secret'] ?? '',
            ],

            'googlePay' => [
                'payment_url' => $settings['google_pay_payment_url'] ?? '',
                'node_server_name' => $settings['google_pay_node_server_name'] ?? '',
            ],

            // NOTE: ZiniPay (services.zinipay.*) and CashFree (payment.cashfree.*)
            // credentials are intentionally NOT sourced from this shared $settings
            // (all_settings cache) array. Their secrets are populated directly from
            // the settings rows in setupPaymentGatewaySecrets() — mirroring the
            // GCS/Centrifugo isolation — so a gateway key never rides the same
            // cache that feeds the public theme/payment config. Activation toggles
            // (is_*_active below) stay here because the panel/front-end read them.

            'is_fawry_active' => $settings['is_fawry_active'] ?? 0,
            'is_paypal_active' => $settings['is_paypal_active'] ?? 0,
            'is_utd_fawry_active' => $settings['is_utd_fawry_active'] ?? 0,
            'is_utd_paymob_active' => $settings['is_utd_paymob_active'] ?? 0,
            'is_paysky_active' => $settings['is_paysky_active'] ?? 0,
            'is_strip_active' => $settings['is_strip_active'] ?? 0,
            'is_opay_active' => $settings['is_opay_active'] ?? 0,
            'is_applepay_active' => $settings['is_applepay_active'] ?? 0,
            'is_google_pay_active' => $settings['is_google_pay_active'] ?? 0,
            'is_codapay_active' => $settings['is_codapay_active'] ?? 0,
            'is_utd_active' => $settings['is_utd_active'] ?? 0,
            'is_cash_free_active' => $settings['is_cash_free_active'] ?? 0,
            'is_zinipay_active' => $settings['is_zinipay_active'] ?? 0,
        ]);

        Cache::put('app_title', $appName, now()->addHours(24));
        Cache::put('host_agency', $settings['host_agency'] ?? 1);
    }

    /**
     * White-label GCS storage: build the Google Cloud Storage credentials from
     * DB admin settings at runtime, with env fallback, and NO hardcoded
     * service-account.json. Owner requirement — cloning a new app fills the
     * settings rows (gcs_service_account_json / gcs_bucket / gcs_project_id) and
     * never touches PHP or ships a key file.
     *
     * Reads settings DIRECTLY (not via cache helper) because the SA JSON is large
     * and per-deploy; it must never leak into the shared all_settings cache that
     * feeds the public theme/payment config. Runs in boot() before any
     * Storage::disk('gcs') call, so the runtime override is picked up by the
     * spatie gcs extender (it reads config at disk-resolution time).
     *
     * Backward-safe: when no DB setting exists, the env-driven config defaults
     * (GOOGLE_CLOUD_*) stay in effect, so [REMOVED] keeps working unchanged.
     */
    protected function setupStorageCredentials(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $rows = Setting::whereIn('key', [
                'gcs_service_account_json',
                'gcs_bucket',
                'gcs_project_id',
            ])->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            // DB not ready (migrate/install): leave env-driven config untouched.
            return;
        }

        $saJson = $rows['gcs_service_account_json'] ?? null;
        $bucket = $rows['gcs_bucket'] ?? null;
        $projectId = $rows['gcs_project_id'] ?? null;

        // Decode the SA JSON into the array the StorageClient expects via key_file
        // (the spatie extender maps key_file -> keyFile). Invalid JSON is ignored
        // so a malformed setting cannot break storage — env fallback stays.
        $keyFileArray = null;
        if (!empty($saJson)) {
            $decoded = json_decode($saJson, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $keyFileArray = $decoded;
                if (empty($projectId) && !empty($decoded['project_id'])) {
                    $projectId = $decoded['project_id'];
                }
            }
        }

        // Nothing configured in DB -> keep the env-driven config as-is.
        if ($keyFileArray === null && empty($bucket) && empty($projectId)) {
            return;
        }

        // Override every disk that uses the gcs driver so all of them share the
        // per-app credentials (gcs, admin, public, profile, rooms, ...).
        foreach ((array) config('filesystems.disks', []) as $name => $diskConfig) {
            if (($diskConfig['driver'] ?? null) !== 'gcs') {
                continue;
            }

            if ($keyFileArray !== null) {
                // key_file (array) takes precedence; clear key_file_path so the
                // StorageClient does not also try to read a (now removed) file.
                config(["filesystems.disks.{$name}.key_file" => $keyFileArray]);
                config(["filesystems.disks.{$name}.key_file_path" => null]);
            }
            if (!empty($projectId)) {
                config(["filesystems.disks.{$name}.project_id" => $projectId]);
            }
            if (!empty($bucket)) {
                config(["filesystems.disks.{$name}.bucket" => $bucket]);
                config(["filesystems.disks.{$name}.url" => 'https://storage.googleapis.com/' . $bucket]);
            }
        }
    }

    /**
     * Dual-driver storage (S3 path). When MEDIA_STORAGE_DRIVER=s3 the 12 media
     * disks (public/admin/rooms/profile/...) switch to driver=s3 in
     * config/filesystems.php, but they carry NO key/secret/region/bucket — the
     * Flysystem S3 adapter needs those in each disk's own config array. Mirror
     * the AWS_* credentials from the standalone `s3` disk (already resolved from
     * env at config-load / config:cache time, so this is cache-safe — never a
     * bare env() outside config files) into every disk whose driver is s3 that
     * is still missing them.
     *
     * No-op for the default gcs deploy: with MEDIA_STORAGE_DRIVER=gcs the only
     * driver=s3 disk is the standalone `s3` disk, which already has its creds, so
     * nothing is injected and GCS behaviour is byte-for-byte unchanged.
     *
     * Only fills MISSING keys (never overwrites an explicit per-disk value), so a
     * disk that already defines its own bucket/region keeps it.
     */
    protected function setupS3StorageCredentials(): void
    {
        $source = (array) config('filesystems.disks.s3', []);

        $shared = [
            'key'                     => $source['key'] ?? null,
            'secret'                  => $source['secret'] ?? null,
            'region'                  => $source['region'] ?? null,
            'bucket'                  => $source['bucket'] ?? null,
            'url'                     => $source['url'] ?? null,
            'endpoint'                => $source['endpoint'] ?? null,
            'use_path_style_endpoint' => $source['use_path_style_endpoint'] ?? null,
        ];

        foreach ((array) config('filesystems.disks', []) as $name => $diskConfig) {
            if (($diskConfig['driver'] ?? null) !== 's3') {
                continue;
            }

            foreach ($shared as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                if (!array_key_exists($key, $diskConfig) || $diskConfig[$key] === null || $diskConfig[$key] === '') {
                    config(["filesystems.disks.{$name}.{$key}" => $value]);
                }
            }
        }
    }

    /**
     * White-label realtime endpoint + secrets: let the admin override the
     * Centrifugo HTTP API base URL AND the three realtime secrets (API key,
     * token HMAC, subscribe-proxy secret) from DB settings at runtime, with env
     * fallback. Owner requirement — cloning a new app fills the settings rows
     * (centrifugo_api_url / centrifugo_api_key / centrifugo_hmac_secret /
     * centrifugo_proxy_secret) and never edits PHP or a committed .env.
     *
     * Mirrors setupStorageCredentials(): reads the rows DIRECTLY (not via the
     * cache helper) so a secret NEVER leaks into the shared all_settings cache
     * that feeds the public theme/payment config. For each value, overrides the
     * SAME config() path its consumer reads:
     *   - api_url       -> centrifugo.api_url + broadcasting.connections.centrifugo.api_url
     *   - api_key       -> centrifugo.api_key + broadcasting.connections.centrifugo.api_key
     *                      (the publisher reads the broadcasting connection config)
     *   - hmac_secret   -> centrifugo.hmac_secret (+ broadcasting connection for parity)
     *   - proxy_secret  -> centrifugo.proxy_secret
     *
     * Backward-safe: an empty row leaves that value's env-driven config
     * untouched, so when every row is blank (current prod) [REMOVED] keeps working
     * unchanged.
     */
    protected function setupRealtimeEndpoint(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $rows = Setting::whereIn('key', [
                'centrifugo_api_url',
                'centrifugo_api_key',
                'centrifugo_hmac_secret',
                'centrifugo_proxy_secret',
            ])->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            // DB not ready (migrate/install): leave env-driven config untouched.
            return;
        }

        $apiUrl      = $rows['centrifugo_api_url'] ?? null;
        $apiKey      = $rows['centrifugo_api_key'] ?? null;
        $hmacSecret  = $rows['centrifugo_hmac_secret'] ?? null;
        $proxySecret = $rows['centrifugo_proxy_secret'] ?? null;

        // Both the dedicated centrifugo config and the broadcasting connection
        // read api_url; override both so the publisher and auth surface agree.
        if (!empty($apiUrl)) {
            config(['centrifugo.api_url' => $apiUrl]);
            config(['broadcasting.connections.centrifugo.api_url' => $apiUrl]);
        }

        // Publisher api_key: CentrifugoBroadcaster reads it from the resolved
        // broadcasting connection config; mirror it onto centrifugo.api_key too.
        if (!empty($apiKey)) {
            config(['centrifugo.api_key' => $apiKey]);
            config(['broadcasting.connections.centrifugo.api_key' => $apiKey]);
        }

        // Token signer (CentrifugoAuthController) reads centrifugo.hmac_secret.
        if (!empty($hmacSecret)) {
            config(['centrifugo.hmac_secret' => $hmacSecret]);
        }

        // Subscribe-proxy auth (VerifyCentrifugoProxy) reads centrifugo.proxy_secret.
        if (!empty($proxySecret)) {
            config(['centrifugo.proxy_secret' => $proxySecret]);
        }

        // Octane safety: BroadcastManager is a persistent singleton that snapshots
        // the centrifugo connection config (api_url + api_key) when the driver
        // first resolves, and is never flushed between requests. Without purging,
        // an admin changing the endpoint/key from the panel would keep publishing
        // with stale credentials until the worker recycles. Purge the resolved
        // driver so the next publish rebuilds from the just-overridden config.
        if ((!empty($apiUrl) || !empty($apiKey)) && \App\Services\OctaneBroadcasterService::isOctane()) {
            try {
                app(\Illuminate\Broadcasting\BroadcastManager::class)->purge('centrifugo');
            } catch (\Throwable $e) {
                // non-fatal: the driver still rebuilds on worker recycle
            }
        }
    }

    /**
     * White-label payment-gateway SECRETS (ZiniPay + CashFree): populate the
     * config paths their consumers read, sourced from DB settings with the
     * env-driven config defaults as fallback only — never a hardcoded key.
     *
     * Mirrors setupStorageCredentials()/setupRealtimeEndpoint(): reads the rows
     * DIRECTLY (not via CacheHelper::cacheSettings) so a gateway secret NEVER
     * leaks into the shared all_settings cache that feeds the public
     * theme/payment config. Only the SECRETS are isolated here; the activation
     * toggles (is_zinipay_active / is_cash_free_active) remain in
     * setupAppSettings() because the admin panel and front-end read them.
     *
     * Preserves the prior `settings ?: env()` precedence exactly:
     *   - services.zinipay.api_key / url            (ZiniPaymentService)
     *   - payment.cashfree.app_id / secret_key / mode (CashFreeController)
     *
     * Backward-safe: a blank/absent row falls back to the env-driven config
     * default, so a deploy that never set these rows behaves unchanged.
     */
    protected function setupPaymentGatewaySecrets(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $rows = Setting::whereIn('key', [
                'zinipay_api_key',
                'zinipay_url',
                'cashfree_app_id',
                'cashfree_secret_key',
                'cashfree_mode',
            ])->pluck('value', 'key')->toArray();
        } catch (\Throwable $e) {
            // DB not ready (migrate/install): leave env-driven config untouched.
            return;
        }

        Config::set([
            'services.zinipay' => [
                'api_key' => ($rows['zinipay_api_key'] ?? '') ?: env('ZINIPAY_API_KEY'),
                'url' => ($rows['zinipay_url'] ?? '') ?: env('ZINIPAY_URL', 'https://api.zinipay.com/v1/payment/create'),
            ],
            'payment.cashfree' => [
                'app_id' => ($rows['cashfree_app_id'] ?? '') ?: env('CASHFREE_APP_ID'),
                'secret_key' => ($rows['cashfree_secret_key'] ?? '') ?: env('CASHFREE_SECRET_KEY'),
                'mode' => ($rows['cashfree_mode'] ?? '') ?: env('CASHFREE_MODE', 'test'),
            ],
        ]);
    }

    protected function setupLanguages(): void
    {
        $enabledLanguages = Cache::rememberForever('languages', function () {
            return Language::where('is_enabled', true)
                ->pluck('name', 'code')
                ->toArray();
        });

        Config::set('admin.extensions.multi-language.languages', $enabledLanguages);
        Config::set('admin.logo', Cache::get('app_title', 'Default Title'));
    }

    protected function registerModelObservers(): void
    {
        User::observe(UserObserver::class);
        Gift::observe(GiftObserver::class);
        Emoji::observe(EmojiObserver::class);
        Ware::observe(WareObserver::class);
        Room::observe(RoomObserver::class);
        UserSallary::observe(UserSallaryObserver::class);
        Family::observe(FamilyObserver::class);
        FamilyUser::observe(FamilyUserObserver::class);
        Pk::observe(PKObserver::class);
        Agency::observe(AgencyObserver::class);
        AreaManager::observe(AreaManagerObserver::class);
        SuperAdmin::observe(SuperAdminObserver::class);
        Bd::observe(BdObserver::class);
        ShippingAgency::observe(ShippingAgencyObserver::class);
        AgencyJoinRequest::observe(AgencyJoinRequestObserver::class);
        Vip::observe(VipObserver::class);
        RoomBoomLevel::observe(RoomBoomLevelObserver::class);
        Setting::observe(SettingObserver::class);
        \App\Models\Config::observe(ConfigObserver::class);
        CoinGameUser::observe(CoinGameUserObserver::class);
        PersonalAccessToken::observe(PersonalAccessTokenObserver::class);

        // Phase 4: centralized cache invalidation for the game config caches so
        // a stale provider/game/reward value can never survive an admin edit.
        \App\Models\GameProviderSetting::observe(\App\Observers\GameProviderSettingObserver::class);
        \App\Models\AllGame::observe(\App\Observers\AllGameObserver::class);
        \App\Models\RewardWinnerGame::observe(\App\Observers\RewardWinnerGameObserver::class);
    }

    protected function cacheLuckyGiftProbabilities(): void
    {
        // Static legacy probability tiers shown in the admin gift-type view
        // (probability_times_1/2/3). Inlined here when the old LuckyGiftService
        // that owned getProbabilityTimes() was removed — pure display data, no
        // engine logic (the live engine draws via MultiplierTable).
        $probabilities = [
            [5, 10, 20],
            [50, 100],
            [250, 500, 1000],
        ];

        foreach ($probabilities as $index => $value) {
            Cache::put('probability_times_' . ($index + 1), $value, now()->addMinutes(60));
        }
    }

    /**
     * Override admin.pjax middleware to avoid exit() which breaks Swoole/Octane
     */
    protected function overridePjaxMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('admin.pjax', \App\Admin\Middleware\PjaxOverride::class);
    }

    /**
     * Register FairLuck V7 engine bindings (the live lucky-gift engine).
     */
    protected function registerFairLuckService(): void
    {
        // V7 engine: all bind() for Octane safety
        $this->app->bind(\App\Services\FairLuck\V7\MultiplierTable::class);
        $this->app->bind(\App\Services\FairLuck\V7\PoolManager::class);
        $this->app->bind(\App\Services\FairLuck\V7\UserRTPTracker::class);
    }
}
