<?php

namespace App\Admin\Controllers;


use Encore\Admin\Auth\Permission;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class CustomController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    public function title()
    {
        return __('custom-page');
    }
    
    public  $permission_name = 'custom-page';


    public function index(Content $content)
    {
        $settings = [
            'chat_enable_version' => settings()->get('chat_enable_version'),
            'android_min_version' => settings()->get('android_min_version'),
            'android_current_version' => settings()->get('android_current_version'),
            'android_update_required' => settings()->get('android_update_required'),

            'ios_min_version' => settings()->get('ios_min_version'),
            'ios_current_version' => settings()->get('ios_current_version'),
            'ios_update_required' => settings()->get('ios_update_required'),

            'huawei_min_version' => settings()->get('huawei_min_version'),
            'huawei_current_version' => settings()->get('huawei_current_version'),
            'huawei_update_required' => settings()->get('huawei_update_required'),

            'chat_status' => settings()->get('chat_status'),
            'invitation_code_date' => settings()->get('invitation_code_date'),
            'show_welcom_enmation' => settings()->get('show_welcom_enmation'),
        ];

        // Route through parent::index so MainController's
        // Permission::check('browse-' . $permission_name) gate runs — the two
        // sibling settings pages (FeatureApp/AppSitiingCOnfig) already do this;
        // this page was returning content directly and skipping the check.
        return parent::index(
            $content->title(__('Custom Settings'))
                ->view('custom_settings', compact('settings'))
        );
    }

}
