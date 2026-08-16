<?php

namespace App\Admin\Controllers;

use App\Models\Setting;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Admin\Controllers\MainController;

class ChargesSettingController extends MainController
{
    protected $title = 'Settings';
    public $permission_name = 'charge-settings';

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $settings = Setting::pluck('value', 'key')->toArray();

        return parent::index($content
            ->header(__('Settings'))
            ->description('')

            ->body(view('admin.charges_settings', compact('settings'))));
    }
}
