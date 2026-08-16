<?php

namespace App\Admin\Controllers;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Config;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoomSettingsController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Room-setting';
    public $permission_name = 'room-settings';

    public function index(Content $content)
    {
        if (!Admin::user()->can('*')) {
            Permission::check('browse-' . $this->permission_name);
        }
        $settings = Config::pluck('value', 'name')->toArray();
        return $content
            ->header(__('Settings'))
            ->description('')
            ->body(view('admin.room_settings', compact('settings')));
    }

    public function store(Request $request): RedirectResponse
    {
        if (!Admin::user()->can('*')) {
            Permission::check('edit-' . $this->permission_name);
        }
        // Handle file uploads for mic images
        $fileFields = ['open_mic_image', 'close_mic_image'];

        $request->validate(
            array_fill_keys(
                $fileFields,
                ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120']
            )
        );

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                $image = Common::upload('images', $file);

                Config::updateOrCreate(['name' => $field], ['value' =>  $image]);
            }
        }

        $data = $request->except(array_merge(['_token'], $fileFields));

        foreach ($data as $key => $value) {
            Config::updateOrCreate(['name' => $key], ['value' => $value]);
        }

        admin_toastr('تم تحديث الإعدادات بنجاح!', 'success');

        return back();
    }
}
