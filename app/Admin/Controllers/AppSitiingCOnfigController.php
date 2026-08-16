<?php

namespace App\Admin\Controllers;


use Illuminate\Support\HtmlString;
use Encore\Admin\Layout\Content;

class AppSitiingCOnfigController extends MainController
{
    public $permission_name = 'updates';
    public function index(Content $content)
    {
        $route = 'admin.postAddSitin';
        $isRTL = app()->getLocale() === 'ar';

        $buttonAlignStyle = $isRTL ? 'text-align: left;' : 'text-align: right;';

        // Get all settings at once for better performance
        $settings = [
            'chat_enable_version' => settings()->get('chat_enable_version'),
            'android_min_version' => settings()->get('android_min_version'),
            'android_current_version' => settings()->get('android_current_version'),
            'android_current_version_name' => settings()->get('android_current_version_name'),
            'android_version_names' => settings()->get('android_version_names'),
            'android_update_required' => settings()->get('android_update_required'),
            'ios_min_version' => settings()->get('ios_min_version'),
            'ios_current_version' => settings()->get('ios_current_version'),
            'ios_current_version_name' => settings()->get('ios_current_version_name'),
            'ios_version_names' => settings()->get('ios_version_names'),
            'ios_update_required' => settings()->get('ios_update_required'),
            'huawei_min_version' => settings()->get('huawei_min_version'),
            'huawei_current_version' => settings()->get('huawei_current_version'),
            'huawei_current_version_name' => settings()->get('huawei_current_version_name'),
            'huawei_version_names' => settings()->get('huawei_version_names'),
            'huawei_update_required' => settings()->get('huawei_update_required'),
            'chat_status' => settings()->get('chat_status'),
            'invitation_code_date' => settings()->get('invitation_code_date'),
            'show_welcom_enmation' => settings()->get('show_welcom_enmation')
        ];

        $form = '<div class="settings-container">';
        $form .= '<form method="POST" action="' . route($route) . '" class="settings-form">';
        $form .= csrf_field();

        $form .= '<style>
            .settings-container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }

            .settings-form {
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-row {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
            }

            .form-col {
                flex: 1;
                background: #f9f9f9;
                padding: 20px;
                border-radius: 6px;
            }

            .platform-title {
                text-align: center;
                color: #2c3e50;
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }

            .control-label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #555;
            }

            .inputs_cus_form {
                width: 100%;
                padding: 10px 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                transition: border-color 0.3s;
            }

            .inputs_cus_form:focus {
                border-color: #3498db;
                outline: none;
                box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
            }

            .inputs_cus_form[readonly] {
                background: #ecf0f1;
                color: #7f8c8d;
                cursor: not-allowed;
            }

            .button_form_cus {
                background: #3498db;
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                transition: background 0.3s;
                display: block;
                width: 100%;
                max-width: 200px;
                margin: 30px auto 0;
            }

            .button_form_cus:hover {
                background: #2980b9;
            }

            /* Switch styles */
            .switch-container {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }

            .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
                margin-left: 15px;
            }

            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: .4s;
                border-radius: 34px;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: .4s;
                border-radius: 50%;
            }

            input:checked + .slider {
                background-color: #2196F3;
            }

            input:focus + .slider {
                box-shadow: 0 0 1px #2196F3;
            }

            input:checked + .slider:before {
                transform: translateX(26px);
            }
            @media (max-width: 576px) {
            }
            @media (max-width: 768px) {
                .form-row {
                    display: block;
                }
            }
            @media (max-width: 992px) {
            }
            @media (max-width: 1200px) {
            }
            @media (max-width: 1400px) {
            }
        </style>';

        // Platform Settings
        $form .= '<div class="form-row">';

        // Android
        $form .= $this->renderPlatformColumn('android', __('admin.android'), $settings);

        // Huawei
        $form .= $this->renderPlatformColumn('huawei', __('admin.huawei'), $settings);

        // iOS
        $form .= $this->renderPlatformColumn('ios', __('admin.ios'), $settings);

        $form .= '</div>'; // Close form-row
        $form .= '<div class="col-sm-12" style="'.$buttonAlignStyle.'">';
        $form .= '<button type="submit" class="btn btn-primary" style=" margin-top:-20px;">' . __('admin.submit') . '</button>';
        $form .= '</div>';

        $form .= '</form>';
        $form .= '</div>';

        return parent::index($content
            ->title(trans('Updates'))
            ->body(new HtmlString($form)));
    }

    /**
     * Render a single platform settings column.
     * Admin interacts with versionName; versionCode comparison is handled server-side.
     */
    private function renderPlatformColumn(string $os, string $title, array $settings): string
    {
        $minCode = $settings[$os . '_min_version'];
        $currentName = $settings[$os . '_current_version_name'];

        $versionNames = json_decode($settings[$os . '_version_names'] ?? '', true);
        if (!is_array($versionNames)) {
            $versionNames = [];
        }

        $selectedName = $versionNames[$minCode] ?? null;

        $col = '<div class="form-col">';
        $col .= '<h2 class="platform-title">' . e($title) . '</h2>';

        // Minimum version -> dropdown of versionName (value = name, handler maps back to code)
        $col .= '<div class="form-group">';
        $col .= '<label for="' . $os . '_min_version" class="control-label">' . __('admin.minimum_version') . '</label>';
        $col .= '<select id="' . $os . '_min_version" name="' . $os . '_min_version" class="inputs_cus_form">';
        foreach ($versionNames as $name) {
            $isSelected = ($selectedName !== null && $name === $selectedName) ? ' selected' : '';
            $col .= '<option value="' . e($name) . '"' . $isSelected . '>' . e($name) . '</option>';
        }
        $col .= '</select>';
        $col .= '</div>';

        // Current version -> read-only versionName (not submitted/edited)
        $col .= '<div class="form-group">';
        $col .= '<label for="' . $os . '_current_version_name" class="control-label">' . __('admin.current_version') . '</label>';
        $col .= '<input type="text" id="' . $os . '_current_version_name" placeholder="' . $os . '_current_version" value="' . e($currentName) . '" class="inputs_cus_form" readonly>';
        $col .= '</div>';

        // Update required -> switch
        $isChecked = ($settings[$os . '_update_required'] == 1) ? ' checked' : '';
        $col .= '<div class="form-group">';
        $col .= '<div class="switch-container">';
        $col .= '<span class="control-label" style="margin-bottom:0;">' . __('admin.force_update') . '</span>';
        $col .= '<label class="switch">';
        $col .= '<input type="checkbox" id="' . $os . '_update_required" name="' . $os . '_update_required" value="1"' . $isChecked . '>';
        $col .= '<span class="slider"></span>';
        $col .= '</label>';
        $col .= '</div>';
        $col .= '</div>';

        $col .= '</div>';

        return $col;
    }
}
