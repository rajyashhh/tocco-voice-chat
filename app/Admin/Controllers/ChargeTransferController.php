<?php

namespace App\Admin\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\AdminController;

class ChargeTransferController extends AdminController
{
    public $permission_name = 'settings';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }

        $settings = Setting::pluck('value', 'key')->toArray();
        return $content
            ->title(__('Charge Transfer Settings'))
            ->description(__('Control charging permissions for different transfer scenarios'))
            ->body(view('admin.settings.charge_transfer', compact('settings')));
    }

    /**
     * Store charge transfer settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function saveSettings(Request $request)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }

        $keys = [
            'charge_user_to_user',
            'charge_user_to_agent',
            'charge_user_to_self',
            'charge_agent_to_user',
            'charge_agent_to_agent',
        ];

        foreach ($keys as $key) {
            $value = $request->input($key, 0);
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget($key);
            Cache::put($key, $value);
        }

        Cache::forget('all_settings');

        admin_toastr(__('Settings updated successfully!'), 'success');

        return redirect(url('admin/charge-transfer-settings'));
    }
}
