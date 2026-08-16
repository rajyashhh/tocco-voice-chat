<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Setting;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;

/**
 * Aggregated notification settings screen (owner 2026-08-12): the EXISTING
 * push-notification keys — previously scattered across the third-party
 * settings tab and the white-label page — surfaced in one place under the
 * Notifications menu section. No new backend keys are introduced here; every
 * field below is already read by a live consumer:
 *
 *  - firebase_project_name / firebase_service_account_json:
 *      Common::firebaseCredentials() (FCM v1 send), DB-first.
 *  - firebase_database_uri: Common::fireBaseFactory().
 *  - fcm_sender_id: legacy group messaging (Common.php).
 *  - notification_default_image: default push image, falls back to app logo.
 *
 * Writes go through the Setting model so SettingObserver flushes the
 * white-label caches + the /firebase-config blob exactly like the existing
 * save paths do.
 */
class NotificationSettingsController extends MainController
{
    public $permission_name = 'notification-setting';

    /**
     * Secret keys are never pre-filled; a blank submit means "keep current"
     * (same contract as ConfigController::updateConfigAgoraZego).
     */
    private const SECRET_KEYS = ['firebase_service_account_json'];

    private const KEYS = [
        'firebase_project_name',
        'firebase_service_account_json',
        'firebase_database_uri',
        'fcm_sender_id',
        'notification_default_image',
    ];

    public function index(Content $content)
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = in_array($key, self::SECRET_KEYS, true)
                ? '' // never echo the secret back to the form
                : (string) (Common::getSettingValue($key) ?? '');
        }

        $secretsConfigured = [];
        foreach (self::SECRET_KEYS as $key) {
            $secretsConfigured[$key] = !empty(Common::getSettingValue($key));
        }

        return parent::index($content
            ->title(__('Notification Settings'))
            ->body(view('admin.notification_settings', [
                'values' => $values,
                'secretsConfigured' => $secretsConfigured,
            ])));
    }

    public function save()
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        foreach (self::KEYS as $key) {
            $value = (string) (request($key) ?? '');

            if (in_array($key, self::SECRET_KEYS, true) && trim($value) === '') {
                continue; // keep the existing secret
            }

            // Saved via the model so SettingObserver flushes the white-label
            // key caches and the firebase_config blob.
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        admin_success(__('Success'), __('Settings updated successfully!'));
        return redirect()->back();
    }
}
