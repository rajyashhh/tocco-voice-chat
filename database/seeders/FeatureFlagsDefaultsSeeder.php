<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Seed the "on by default" state for every in-app feature toggle.
 *
 * The feature middlewares read `Setting::where('key',$k)->value('value') ?? 0`,
 * so a MISSING row means the feature is OFF. On a clean install none of these
 * rows exist, so every feature (moments, room cup, room boom, PK, host level,
 * charisma, chat, …) shipped disabled — the user hit "هذه الميزة غير مفعلة".
 * The product rule is the opposite: every feature an app ships MUST default to
 * enabled, and the admin turns OFF what they don't want. This plants those
 * enabled defaults from the root so any new client is fully-featured out of the
 * box.
 *
 * Insert-only: we ONLY create a row when the key is absent. An existing install
 * where the admin already flipped a toggle keeps that choice untouched — this
 * never overwrites a deliberate value. The paired/string-valued keys use the
 * exact shape each reader compares against (see the note per group).
 */
class FeatureFlagsDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        // key => enabled value expected by its reader.
        // Numeric readers compare truthiness (1). String readers compare == 'on'.
        $defaults = [
            // Moments (Modules/Moment CheckAllowedMoment: value ?? 0)
            'moment_status'              => '1',

            // Room Cup (RoomCupMiddleware + CheckAllowedApp read BOTH keys)
            'room_cup'                   => '1',
            'room_cup_setting'           => '1',

            // Room Boom (RoomBoomMiddleware requires BOTH truthy)
            'room_boom'                  => '1',
            'enable_room_boom'           => '1',

            // PK live action (PkLiveMiddleware: value ?? 0)
            'pk_live_action'             => '1',

            // Remaining diamonds (RemainingDiamondsMiddleware: value ?? 0)
            'remaining_diamonds_action'  => '1',

            // Host level (HostLevelMiddleware gates on host_level_action;
            // HostLevelActionMiddleWare reads host_level_action too)
            'host_level_enabled'         => '1',
            'host_level_action'          => '1',

            // Charisma badge (CharismaBadgeMiddleware: value ?? 0)
            'charisma_badge'             => '1',

            // Chat (VersionController / AppSettingResource compare == 'on')
            'chat_status'                => 'on',

            // Welcome-in animation (EnterRoomCollection compares == 'on')
            'show_welcom_enmation'       => 'on',

            // Salary transfer (AppSettingResource: value ?? 0)
            'transfer_salary'            => '1',

            // Cinema / YouTube mode (RoomRepoService default already true, but
            // pin it so the panel toggle reflects the real state)
            'youtube_status'             => '1',

            // ── Full-edition features absent from the voice edition ───────────
            // Reels feed (FeatureAppController writes '1'/'0'; VersionController
            // reads (bool)($settings['reel_status'] ?? true) — default-ON. Pin so
            // the panel toggle reflects the shipped enabled state.)
            'reel_status'                => '1',

            // Live broadcast (FeatureAppController writes '1'/'0'; VersionController
            // reads (bool)($settings['live_status'] ?? true) — default-ON.)
            'live_status'                => '1',

            // Audio room (EnteranceController/RoomController: getSettingValue ?? 1;
            // panel default $settings['audio_room'] ?? 1 — default-ON.)
            'audio_room'                 => '1',
        ];

        foreach ($defaults as $key => $value) {
            $exists = Setting::where('key', $key)->exists();
            if ($exists) {
                continue; // never overwrite an admin's deliberate choice
            }

            Setting::create(['key' => $key, 'value' => $value]);
            // The readers cache with rememberForever; drop any stale miss so the
            // new default is seen immediately without a full cache flush.
            Cache::forget($key);
        }
    }
}
