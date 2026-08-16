<?php

namespace App\Admin\Controllers;

use App\Models\Setting;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\Cache;

class RemainingDiamondSettingController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */

    public $permission_name = 'remaining-diamond-setting';

    public function index(Content $content)
    {
        $settings = $this->getSettings();

        return parent::index($content
            ->title(__('remaining diamonds settings'))
            ->body(view('remainingDiamondSetting', [
                'settings' => $settings,
                'saveUrl'  => $this->saveUrl(),
            ])));
    }

    private function saveUrl()
    {
        return admin_url('remaining-diamond-settings/save');
    }

    private function getSettings()
    {
        $default = [
            'remaining_diamonds'     => 'nothing',
        ];

        $settings = [];

        foreach ($default as $key => $defaultValue) {
            $cacheKey =   $key;
            $value = Cache::get($cacheKey);

            if ($value === null) {
                $setting = Setting::where('key', $cacheKey)->first();
                $value = $setting ? $setting->value : $defaultValue;

                Cache::put($cacheKey, $value);
            }



            $settings[$key] = $value;
        }

        return $settings;
    }

    public function save()
    {
        Permission::check('edit-' . $this->permission_name);

        $data = [
            'remaining_diamonds'     => request('remaining_diamonds', 'nothing'),
        ];


        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' =>  $key],
                ['value' => $value]
            );

            Cache::put($key, $value);
        }

        admin_success('تم الحفظ بنجاح ✅');
        return redirect()->back();
    }
}
