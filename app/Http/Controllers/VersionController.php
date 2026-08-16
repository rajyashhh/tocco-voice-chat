<?php

namespace App\Http\Controllers;

use App\Facades\UserHandling;
use App\Helpers\CacheHelper;
use App\Helpers\Common;
use App\Models\Config;
use App\Models\Setting;
use App\Models\User;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VersionController extends Controller
{
    public function versionAndCache(Request $request)
    {
        $version = $request->version;
        $versionName = $request->version_name;

        $osPrefix = $request->OS == 'Huawei' ? 'huawei' : ($request->OS == 'IOS' ? 'ios' : 'android');

        $versionNames = json_decode(settings()->get($osPrefix . '_version_names') ?? '[]', true);
        if (!is_array($versionNames)) $versionNames = [];

        // Only persist the version-names map when this build is genuinely new
        // (or its name changed). The old code wrote on EVERY request, turning the
        // client open endpoint into a constant settings.json writer and a prime
        // source of the lost-update race. Reads/response data are unchanged.
        if ($versionName !== null && intval($version) > 0) {
            $versionKey = strval(intval($version));
            if (($versionNames[$versionKey] ?? null) !== $versionName) {
                $versionNames[$versionKey] = $versionName;
                settings()->set($osPrefix . '_version_names', json_encode($versionNames));
            }
        }

        $existingCurrent = intval(settings()->get($osPrefix . '_current_version') ?? 0);
        $newCurrent = max($existingCurrent, intval($version));
        if ($newCurrent !== $existingCurrent) {
            settings()->set($osPrefix . '_current_version', $newCurrent);
        }
        if (isset($versionNames[strval($newCurrent)])
            && settings()->get($osPrefix . '_current_version_name') !== $versionNames[strval($newCurrent)]) {
            settings()->set($osPrefix . '_current_version_name', $versionNames[strval($newCurrent)]);
        }

        $currentVersion  = settings()->get($request->OS == 'Huawei' ? 'huawei_current_version' : ($request->OS == 'IOS' ? 'ios_current_version' : 'android_current_version'));

        /*if ($version > $currentVersion){
            return Common::apiResponse(false, __('api_responses.disabled_version'));
        }*/
        $authorizationHeader = $request->header('Authorization');
        $token               = $this->getTokenFromHeader($authorizationHeader);
        [$isAuth, $user] = $this->isAuth($token);
        $settings = $this->getSettingsArray();
        $appUrl = '';
        if ($user) {
            try {
                if ($request->OS == 'Android' && $user->android_version != $version) {
                    DB::table('users')->where('id', $user->id)->update(['android_version' => intval($version)]);
                } else if ($request->OS == 'IOS' && $user->ios_version != $version) {
                    DB::table('users')->where('id', $user->id)->update(['ios_version' => intval($version)]);
                } else if ($request->OS == 'Huawei' && $user->huawei_version != $version) {
                    DB::table('users')->where('id', $user->id)->update(['huawei_version' => intval($version)]);
                }
            } catch (\Exception $e) {
            }
            //            $user->update(['android_version' => $version]);
        }

        $links = [
            'Android' => $settings['android_link'] ?? null,
            'IOS'     => $settings['ios_link'] ?? null,
            'Huawei'  => $settings['huawei_link'] ?? null,
        ];

        $appUrl = $links[$request->OS] ?? null;

        $isBan          = $this->haveBan(@$user->uuid);
        $isGiftUpdated  = $this->isUpdated('gifts_update_at', @$request->gift_time);
        $isIntroUpdated = $this->isUpdated('intro_updated_at', @$request->intro_time);
        $isBubbleFrameUpdated = $this->isUpdated('bubble_frame_updated_at', @$request->bubbles_frame_time);
        $isFrameUpdated = $this->isUpdated('frame_updated_at', @$request->frame_time);
        $isEmojiUpdated = $this->isUpdated('emoji_updated_at', @$request->emoji_time);
        $isExtraUpdated = $this->isUpdated('extra_updated_at', @$request->extra_time);
        $agencyBadges = $this->isUpdated('badges_agency_update_at', @$request->badges_agency_time);
        $wapple = $this->isUpdated('wappel_frame_updated_at', @$request->wabbles_frame_time);
        $isColorSettingUpdated = $this->isUpdated('color_setting_updated_at', @$request->color_time);
        $ProfileFrameUpdated = $this->isUpdated('profile_frame_updated', @$request->profile_frame_updated);
        // The app sends its boom-videos time as `room_boom_videos` (see the
        // config_app request body); read that exact param so a panel update
        // actually reaches the fleet instead of always comparing against null.
        $isRoomBoomVideoUpdated = $this->isUpdated('room_boom_video_update_at', @$request->room_boom_videos);

        $boomThemes = $this->isUpdated('boom_themes', @$request->boom_themes_time);

        // The app sends its games-image time as `games_image_time` (was read here
        // as the non-existent `images_time`, so it never matched and — with the
        // old true-by-default — force-refetched every cold start).
        $images = $this->isUpdated('images_updated_at', @$request->games_image_time);
        // The app sends no separate ground time: the colors flow already carries
        // the background (handleColors persists it from colors/v2), so background
        // is driven by the colors version, not a second forced fetch.
        $ground = $this->isUpdated('ground_updated_at', @$request->ground_time);
        $colorsUpdate = $this->isUpdated('colors_updated_at', @$request->colors_updated_time);

        $default_background =  \DB::table('backgrounds')->where('enable', 1)->orderBy('id')->value('img');

        $maxMessage = \Cache::rememberForever('max_message', function () {
            $setting =   Config::where('name', 'max_message')->first();
            return $setting?->value ?? 3;
        });

        $micImages = Config::whereIn('name', ['open_mic_image', 'close_mic_image'])->pluck('value', 'name');



        $data = [
            'is_auth'         => $isAuth && !$isBan,
            // Show the update prompt ONLY when the user is below the admin-set
            // minimum version. Previously this keyed off `current_version`, which
            // auto-bumps (line ~36) to the highest build ANY user/tester reports —
            // so a single newer build made everyone on the live store version see a
            // spurious "update" prompt even while on the latest published release.
            'is_last_version' => (int)$version >= (int) settings()->get($osPrefix . '_min_version'),
            'is_force'        => $this->isForce($version, $request->OS),
            'is_show_shipping_agencies' => true,
            'images' => $images,
            'badges-agency' =>  $agencyBadges,
            'cache_update' => [
                'gifts'  => $isGiftUpdated,
                'intro' => $isIntroUpdated,
                'emoji' => $isEmojiUpdated,
                'frames' => $isFrameUpdated,
                'extras' => $isExtraUpdated,
                'profile_frame_updated' => $ProfileFrameUpdated,
                'bubble_frame' => $isBubbleFrameUpdated,
                'room_boom_videos' => $isRoomBoomVideoUpdated,
                'wapple' => $wapple ?? false,
                'colors' =>  $colorsUpdate,
                'background' => $ground,
                'host_agency' => (bool) ($settings['host_agency'] ?? true),
                'color_time'  => $isColorSettingUpdated,
                //intro - frames - extradata - emoji
            ],
            'enable_chat'  => settings()->get('chat_status') == "on",
            'reel_status'    => (bool) ($settings['reel_status'] ?? true),
            'youtube_status' => (bool) ($settings['youtube_status'] ?? true),
            'youtube_api_key' => (string) ($settings['youtube_api_key'] ?? ''),
            'live_status'    => (bool) ($settings['live_status'] ?? true),
            'zego_feature'    => (bool) ($settings['zego_feature'] ?? true),
             'charisma_badge'    => (bool) ($settings['charisma_badge'] ?? false),
            'default_room_background'    => $default_background ?? '',
            'is_show_room_activity' => ($settings['room_cup'] ?? 0) == 1 || ($settings['room_cup_setting'] ?? 0) == 1,
            'is_pk_live_active' => (bool) ($settings['pk_live_action'] ?? false),
            'app_url' => @$appUrl,
            'is_new_theme_enabled' => (bool) ($settings['is_new_theme_enabled'] ?? false),
            'app_ui_variant' => $settings['app_ui_variant'] ?? '',
            'is_dark_mode_enabled' => (bool) ($settings['is_dark_mode_enabled'] ?? false),
            'is_body_theme_enabled' => (bool) ($settings['is_body_theme_enabled'] ?? false),
            'background_body_theme' => [
                'type' => $settings['background_body_theme'] ?? 'color',
                'color' => $settings['background_body_theme_color'] ?? '#FFFFFF',
                'gradient_one' => $settings['background_body_theme_color_one'] ?? '#FFFFFF',
                'gradient_two' => $settings['background_body_theme_color_two'] ??'#FFFFFF',
                'gradient_three' => $settings['background_body_theme_color_three'] ?? '#FFFFFF',
                'image' => $settings['background_body_theme_image'] ?? '',
            ],
            $settings['background_body_theme'] ?? 'color',
            'moment_status'  => (bool) ($settings['moment_status'] ?? true),
            'is_show_host_levels' =>
            intval($settings['host_level_action'] ?? 0) === 1
                && intval($settings['host_level_enabled'] ?? 0) === 1,
            "is_share_with_friends" => (bool)($settings['share_room_with_friends'] ?? true),
            'is_show_grid_view' => (bool) Common::getConf('show_room') ?? false,
            'limit_chat_message' => (int) $maxMessage,
            'active_mode' => collect([
                6 => $settings['room_mode_6'] ?? 0,
                7 => $settings['room_mode_7'] ?? 0,
                8 => $settings['room_mode_8'] ?? 0,
                9 => $settings['room_mode_9'] ?? 0,
            ])->filter(fn($value) => $value == 1)->keys()->values()->toArray(),

            "room_boom" => [
                "enabled" => ($settings['enable_room_boom'] ?? 0) && ($settings['room_boom'] ?? 0),
                "cache_assets" => $boomThemes,
            ],

            "audio_room_enabled" => [
                "enable" => (bool) ($settings['audio_room'] ?? true),
                "metadata" => [
                    "default_screen" => $settings['default_screen'] ?? 'audio_room',
                ]
            ],
            'mic_images' => [
                'open'  => $micImages['open_mic_image'] ?? '',
                'close' => $micImages['close_mic_image'] ?? '',
            ],


        ];

        //update current version for user

        return response()->json($data);
    }

    private function getTokenFromHeader(?string $authorizationHeader)
    {
        //remove bearer word from header auth
        $token = substr($authorizationHeader, 7);
        //split from id|token to $token
        $tokens = explode('|', $token);

        if (count($tokens) == 2) $token = $tokens[1];
        elseif (count($tokens) == 1) $token = $tokens[0];
        else $token = null;
        return $token;
    }

    private function isAuth($token): array
    {

        $user = Auth::guard('sanctum')->user();
        return [$user != null, $user];
    }

    // private function isForce($version)
    // {
    //     $isForce = false;

    //     if ($version < settings()->get('android_min_version')) $isForce = true;

    //     if (settings()->get('android_update_required') == 1 && $version != settings()->get('android_current_version') ) $isForce = true;

    //     return $isForce;
    // }

    private function haveBan(?string $uuid): bool
    {
        if ($uuid == null) return false;

        return UserHandling::haveBan($uuid, \request());
    }

    /**
     * @param $key
     * @param $time
     * @return bool
     */
    public function isUpdated($key, $time): bool
    {
        // Absent client time (fresh install, an old build that never sent this
        // key, or a brand-new section the client doesn't yet persist) means "I
        // don't know my time" — NOT "force a refetch". Returning true here was
        // the bug: it pushed the whole fleet to refetch colors/gifts/frames on
        // every cold start. The safe default is false: only refetch when the
        // client gives a time AND the server's published value is strictly newer.
        if ($time === null || $time === '' || (int) $time === 0) {
            return false;
        }

        $time = (int) $time;
        if ($time > 9999999999) {
            // Convert milliseconds to seconds
            $time = (int) ($time / 1000);
        }

        $serverUpdatedAt = settings()->get($key);
        if ($serverUpdatedAt === null) {
            // Server never published a timestamp for this section -> nothing new.
            return false;
        }

        return (int) $serverUpdatedAt > $time;
    }

    private function isForce($version, $OS)
    {
        $isForce     = false;
        $minKey      =
            $OS == 'Huawei' ? 'huawei_min_version' : ($OS == 'IOS' ? 'ios_min_version' : 'android_min_version');
        $requiredKey =
            $OS == 'Huawei' ? 'huawei_update_required' : ($OS == 'IOS' ? 'ios_update_required' : 'android_update_required');
        $currentKey  =
            $OS == 'Huawei' ? 'huawei_current_version' : ($OS == 'IOS' ? 'ios_current_version' : 'android_current_version');

        // الحظر يعتمد فقط على سويتش "تحديث إجباري": مطفي = صفر حظر مهما كان الحد الأدنى؛
        // مفعّل = يُجبَر أي إصدار أقل من الحد الأدنى. يمنع حظر كل المستخدمين بإصدار غير منشور.
        if (settings()->get($requiredKey) == 1 && $version < settings()->get($minKey)) $isForce = true;

        return $isForce;
    }

    /**
     * @return mixed
     */
    public function getSettingsArray()
    {
        $settings = Cache::get('all_settings');
        if (!$settings) {
            $settings = CacheHelper::cacheSettings();
        }

        if (!$settings) {
            return [];
        }

        return $settings->whereIn('key', ['reel_status','background_body_theme','background_body_theme_image','background_body_theme_color','background_body_theme_color_three','background_body_theme_color_two','background_body_theme_color_one','is_dark_mode_enabled','is_body_theme_enabled', 'audio_room', 'default_screen', 'youtube_status', 'youtube_api_key', 'share_room_with_friends', 'live_status', 'host_agency', 'zego_feature', 'huawei_link', 'host_level_enabled', 'charisma_badge', 'host_level_action', 'ios_link', 'android_link', 'room_cup', 'room_cup_setting', 'is_new_theme_enabled', 'app_ui_variant', 'room_boom', 'enable_room_boom', 'pk_live_action', 'room_mode_9', 'room_mode_7', 'room_mode_6', 'room_mode_8', 'moment_status'])->pluck('value', 'key')->toArray();
    }



    private function updateUserCurrentVersion(?User $user, $version): bool
    {
        if (!$user) return false;
        if ($version == null || $user->current_app_version == $version) return false;

        $user->current_app_version = $version;
        $user->save();
        return true;
    }

    public function settings()
    {
        // ── Realtime transport (Centrifugo only) ────────────────────────────
        // Pusher has been removed: Centrifugo is the single realtime transport.
        //   realtime_chat_users    : 'all' | '123,456' | null    (per-user chat canary)
        //   realtime_banners_users : 'all' | '123,456' | null    (per-user banner canary)
        //   centrifugo_ws          : the wss endpoint the client connects to
        // The allow-lists still gate the per-user rollout of the chat path and the
        // outside-room banners independently; each can be widened/rolled back in a
        // single admin toggle. The legacy `pusher` key is kept (null) so older
        // clients that still read it never crash on a missing field.
        $allowList = Common::getConf('realtime_chat_users');
        $bannersAllowList = Common::getConf('realtime_banners_users');
        $uid = optional(auth()->user())->id;

        $useRealtime = $this->userInRealtimeAllowList($uid, $allowList);
        $useRealtimeBanners = $this->userInRealtimeAllowList($uid, $bannersAllowList);

        $data = [
            'pusher' => null,
            'realtime_transport' => Common::getConf('realtime_transport') ?: 'centrifugo',
            'use_realtime_chat' => $useRealtime,
            'use_realtime_banners' => $useRealtimeBanners,
            'centrifugo_ws' => Common::getSettingValue('centrifugo_ws') ?: (Common::getConf('centrifugo_ws') ?: ''),
            // UTD Stream room credentials (admin-editable, instant flip, no app
            // rebuild). White-label: resolved from the configs table per deploy;
            // a clone ships with empty credentials and NEVER carries a foreign
            // engine secret as a code default.
            'utd_stream_app_id' => Common::getConf('utd_stream_app_id') ?: '',
            // The client talks to UTD Stream directly (latency), so it needs the
            // secret — sourced ONLY from the admin-set DB key (utd_stream_server_secret,
            // the same key the backend itself uses). No hardcoded/foreign default;
            // a clone with a blank setting simply sends '' until its admin fills it.
            'utd_stream_app_secret' => Common::getConf('utd_stream_server_secret') ?: '',
            // Admin-set UTD Stream app key, delivered alongside the id/secret in
            // this same settings payload — same DB-only, no-code-default rule as
            // the two keys above.
            'utd_stream_app_key' => Common::getConf('utd_stream_app_key') ?: '',
            // Engine host for the CLIENT kits (token + in-room ops). Derived from
            // the same admin-set utd_stream_base_url the backend uses (which
            // carries an /api/v1 suffix the kits add themselves). Empty when the
            // admin left the default — the kits then fall back to their built-in
            // production hosts, so only a non-default engine (e.g. the shared
            // test engine) needs this set.
            'utd_stream_host' => rtrim(preg_replace('#/api/v1/?$#', '', (string) (Common::getConf('utd_stream_base_url') ?: '')), '/'),
            // White-label per-app values the client applies at runtime (no code
            // default — a clone sends '' until its admin fills them). storage_url
            // reuses the admin-set object-storage bucket; privacy/support are
            // optional admin config keys.
            'storage_url' => self::resolveStoragePublicUrl(),
            'privacy_policy_url' => Common::getConf('privacy_policy_url') ?: '',
            'support_whatsapp' => Common::getConf('support_whatsapp') ?: '',
            // In-app content screens (About Us + Privacy Policy), bilingual and
            // admin-editable from the Brand settings panel (Settings table). The
            // SINGLE panel-managed source for both screens — a clone ships blank
            // and shows nothing until its admin fills them. No brand/client name
            // is ever hardcoded in the app.
            'about_us_ar' => Common::getSettingValue('about_us_ar') ?: '',
            'about_us_en' => Common::getSettingValue('about_us_en') ?: '',
            // Panel-managed app display name (Brand settings -> Application
            // Titles). The client prefers this over its native label so the
            // admin's brand name wins everywhere in-app without a rebuild.
            'app_title_ar' => Common::getSettingValue('app_title_ar') ?: '',
            'app_title_en' => Common::getSettingValue('app_title_en') ?: '',
            'privacy_policy_ar' => Common::getSettingValue('privacy_policy_ar') ?: '',
            'privacy_policy_en' => Common::getSettingValue('privacy_policy_en') ?: '',
            // Admin-set Google OAuth web client id: the app's GoogleSignIn
            // factory prefers this over the baked google-services.json default,
            // so a panel change takes effect without a rebuild. '' = use baked.
            'google_client_id' => Common::getSettingValue('google_client_id') ?: '',
        ];
        return Common::apiResponse(true, '', $data);
    }

    /**
     * Public base URL for object storage (white-label). Prefers an explicit
     * admin 'storage_url' setting; else derives the GCS public base from the
     * admin-set bucket. Empty when nothing is configured — never a foreign default.
     */
    private static function resolveStoragePublicUrl(): string
    {
        $explicit = Common::getSettingValue('storage_url');
        if (is_string($explicit) && $explicit !== '') {
            return rtrim($explicit, '/') . '/';
        }

        $bucket = Common::getSettingValue('gcs_bucket');
        if (is_string($bucket) && $bucket !== '') {
            return 'https://storage.googleapis.com/' . $bucket . '/';
        }

        return '';
    }

    /**
     * Per-user canary gate for the new realtime chat. 'all' opens it for
     * everyone; a CSV of user ids limits it to those accounts; null/empty keeps
     * it off (the safe default during the migration).
     */
    private function userInRealtimeAllowList($uid, $allowList): bool
    {
        if ($allowList === null || $allowList === '') {
            return false;
        }
        if (trim(strtolower($allowList)) === 'all') {
            return true;
        }
        if (!$uid) {
            return false;
        }
        $ids = array_filter(array_map('trim', explode(',', $allowList)));
        return in_array((string) $uid, $ids, true);
    }
}
