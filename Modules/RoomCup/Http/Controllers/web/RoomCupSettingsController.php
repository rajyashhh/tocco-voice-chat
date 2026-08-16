<?php

namespace Modules\RoomCup\Http\Controllers\web;

use Encore\Admin\Form;
use App\Models\Setting;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Admin\Controllers\MainController;
use Encore\Admin\Controllers\AdminController;

class RoomCupSettingsController extends MainController
{
    protected $title = '';
     public $permission_name = 'room-cup-settings';

    public function index(Content $content)
    {
        $settings = $this->getSettings();

        return parent::index($content
            ->title(__('Room Cup Settings'))
            ->body(view('roomcup::room_cup_settings', [
                'settings' => $settings,
                'saveUrl'  => $this->saveUrl(),
            ])));
    }

    private function saveUrl()
    {
        return admin_url('room-cup-settings/save');
    }

    private function getSettings()
    {
        $default = [
            'enabled'  => true,
            'type'     => 'daily',
            'time'     => '00:00',
            'day'      => 0,
            'interval' => 1,
        ];

        $settings = [];

        foreach ($default as $key => $defaultValue) {
            $cacheKey = 'roomcup_' . $key;
            $value = Cache::get($cacheKey);

            if ($value === null) {
                $setting = Setting::where('key', $cacheKey)->first();
                $value = $setting ? $setting->value : $defaultValue;

                Cache::put($cacheKey, $value, now()->addDays(30));
            }

            if ($key === 'enabled') {
                $value = (bool) $value;
            } elseif (in_array($key, ['day', 'interval'])) {
                $value = (int) $value;
            }

            $settings[$key] = $value;
        }

        return $settings;
    }




    public function save()
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $data = [
            'enabled'  => request()->has('enabled'),
            'type'     => request('type', 'daily'),
            'time'     => request('time', '00:00'),
            'day'      => (int) request('day', 0),
            'interval' => (int) request('interval', 1),
        ];


        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => 'roomcup_' . $key],
                ['value' => $value]
            );

            Cache::forget('roomcup_' . $key);
            Cache::put('roomcup_' . $key, $value, now()->addDays(30));
        }

        admin_success('تم الحفظ بنجاح ✅');
        return redirect()->back();
    }
}
