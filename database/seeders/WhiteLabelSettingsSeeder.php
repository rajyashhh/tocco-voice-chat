<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * White-label settings seeder.
 *
 * Seeds every per-app IDENTITY / endpoint / storage key as an EMPTY row so the
 * admin "App / White-Label Settings" page can edit them and the
 * Common::whiteLabel() reads resolve DB-first. Values are intentionally blank:
 *
 *   - A FRESH CLONE runs this seeder and gets zero base-deployment values; it fills the
 *     rows from the admin page (or sets the per-app .env fallbacks).
 *   - The base deployment keeps working unchanged: every consumer falls back to its existing
 *     env/config when the row is empty, so seeding blanks changes nothing live.
 *
 * Uses firstOrCreate (NOT updateOrCreate) so re-running NEVER overwrites a value
 * an owner already entered in the panel.
 */
class WhiteLabelSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $keys = [
            // Identity
            'app_origin_name'            => 'App origin name (per-app identity)',
            'google_client_id'           => 'Google OAuth client id (Sign-in audience)',
            'utd_client_id'              => 'UTD client id (X-Encrypt header validation)',
            'utd_secret_key'             => 'UTD AES shared secret (X-Encrypt validation)',
            'google_play_package_name'   => 'Android package name (Google Play IAP verification)',

            // Firebase / FCM
            'fcm_sender_id'              => 'FCM sender / project id (legacy group messaging)',
            'firebase_project_name'      => 'Firebase project id (FCM v1 send)',
            'firebase_database_uri'      => 'Firebase Realtime Database URI',
            'notification_default_image' => 'Default push notification image (falls back to app logo)',

            // Firebase Phone Auth (SMS)
            'firebase_phone_auth_enabled' => 'Enable Firebase phone (SMS) authentication',
            'firebase_web_api_key'        => 'Firebase Web API key (Identity Toolkit / phone auth)',
            'firebase_auth_domain'        => 'Firebase auth domain (project.firebaseapp.com)',

            // Phone OTP provider switch + Twilio SMS. Blank provider means
            // firebase (OtpProviderService default) — zero behavior change.
            'phone_otp_provider'          => 'Active phone OTP provider: firebase | twilio | whatsapp',
            'twilio_account_sid'          => 'Twilio Account SID (SMS OTP delivery)',
            'twilio_auth_token'           => 'Twilio Auth Token (SMS OTP delivery)',
            'twilio_from_number'          => 'Twilio sender number (E.164, e.g. +15005550006)',

            // Firebase App identity (Crashlytics / Auth / Firestore) — per-platform
            // client config served by /firebase-config so a clone configures its
            // own Firebase from the panel (no google-services.json / plist edit).
            'firebase_app_android_api_key'    => 'Firebase Android app API key',
            'firebase_app_android_app_id'     => 'Firebase Android app id (1:NNN:android:xxx)',
            'firebase_app_android_sender_id'  => 'Firebase Android messaging sender id (project number)',
            'firebase_app_android_project_id' => 'Firebase Android project id',
            'firebase_app_android_bucket'     => 'Firebase Android storage bucket',
            'firebase_app_ios_api_key'        => 'Firebase iOS app API key',
            'firebase_app_ios_app_id'         => 'Firebase iOS app id (1:NNN:ios:xxx)',
            'firebase_app_ios_sender_id'      => 'Firebase iOS messaging sender id (project number)',
            'firebase_app_ios_project_id'     => 'Firebase iOS project id',
            'firebase_app_ios_bucket'         => 'Firebase iOS storage bucket',

            // Realtime
            'centrifugo_api_url'         => 'Centrifugo HTTP API base URL',
            'centrifugo_api_key'         => 'Centrifugo HTTP API key (X-API-Key header for publish/broadcast)',
            'centrifugo_hmac_secret'     => 'Centrifugo token HMAC secret (HS256 connection + subscription JWTs)',
            'centrifugo_proxy_secret'    => 'Centrifugo subscribe-proxy shared secret (node->Laravel auth)',

            // Media
            'utd_media_analyze_url'      => 'UTD media-analyze endpoint URL (mp4 type detection)',

            // Storage (GCS) — SA JSON kept in DB per owner requirement
            'gcs_service_account_json'   => 'GCS service account JSON (full key file content)',
            'gcs_bucket'                 => 'GCS bucket name',
            'gcs_project_id'             => 'GCS / GCP project id',
        ];

        foreach ($keys as $key => $description) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => '']
            );
        }
    }
}
