<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Models\Setting;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Cache;
use App\Admin\Controllers\MainController;


class AppDefaultScreenSettingsController extends MainController
{
    public $permission_name = 'default-screen-settings';

    /**
     * The app reads ONE key (default_screen) from /config/settings:
     * 'audio_room'|'live' both open the Home page — audio_room lands on the
     * Party (rooms) section, live lands on the Live section. The panel now
     * exposes that as the two questions the owner actually asks: which screen
     * first, and — when Home — which Home section first. Stored value stays
     * the same single key, so no app change is needed.
     */
    public function index(Content $content)
    {
        $stored = Common::getSettingValue('default_screen') ?? 'audio_room';

        $isHome = in_array($stored, ['audio_room', 'live'], true);
        $screen = $isHome ? 'home' : $stored;
        $homeSection = $isHome ? ($stored === 'live' ? 'live' : 'party') : 'party';

        $audioEnabled = (Common::getSettingValue('audio_room') ?? 1) == 1;
        $liveEnabled = (Common::getSettingValue('live_status') ?? 1) == 1;

        $screenOptions = [];
        if ($audioEnabled || $liveEnabled) {
            $screenOptions['home'] = __('Home');
        }
        if ((Common::getSettingValue('reel_status') ?? 1) == 1) {
            $screenOptions['reels'] = __('Reels');
        }
        if ((Common::getSettingValue('chat_status') ?? 1) == 1) {
            $screenOptions['chat'] = __('Chat');
        }
        if ((Common::getSettingValue('moment_status') ?? 1) == 1) {
            $screenOptions['moment'] = __('Moment');
        }
        if ((Common::getSettingValue('game_status') ?? 1) == 1) {
            $screenOptions['game'] = __('Game');
        }

        $sectionOptions = [];
        if ($audioEnabled) {
            $sectionOptions['party'] = __('Party (Audio Rooms)');
        }
        if ($liveEnabled) {
            $sectionOptions['live'] = __('Live Broadcast');
        }

        return parent::index(
            $content
                ->title(__('Default App Screen Settings'))
                ->body(view('defaultScreen', [
                    'screen' => $screen,
                    'homeSection' => $homeSection,
                    'screenOptions' => $screenOptions,
                    'sectionOptions' => $sectionOptions,
                    'saveUrl' => $this->saveUrl(),
                ]))
        );
    }

    private function saveUrl()
    {
        return admin_url('default-app-screen');
    }


    public function store()
    {
        Permission::check('edit-' . $this->permission_name);

        $screen = request('screen');
        $homeSection = request('home_section', 'party');

        if (!in_array($screen, ['home', 'moment', 'game', 'reels', 'chat'], true)
            || !in_array($homeSection, ['party', 'live'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid type'], 400);
        }

        $type = $screen === 'home'
            ? ($homeSection === 'live' ? 'live' : 'audio_room')
            : $screen;

        Setting::updateOrCreate(
            ['key' => 'default_screen'],
            ['value' => $type]
        );
        Cache::put('default_screen', $type);
        admin_success(__('done successfully ✅'));
        return redirect()->back();
    }
}
