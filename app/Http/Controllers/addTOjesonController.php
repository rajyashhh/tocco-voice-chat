<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Encore\Admin\Auth\Permission;
use Encore\Admin\Facades\Admin;

class addTOjesonController extends Controller
{
    public $permission_name = 'updates';
    public function custom(Request $request){
        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }
        //chat configs
        $chat_status =  $request->chat_status;
        $chat_enable_version =  $request->chat_enable_version;
        $invitation_code_date =  $request->invitation_code_date;
        $show_welcom_enmation =  $request->show_welcom_enmation;
        settings()->set("chat_status", $chat_status);
        settings()->set("chat_enable_version", $chat_enable_version);
        settings()->set("invitation_code_date", $invitation_code_date);
        settings()->set("show_welcom_enmation", $show_welcom_enmation);

        return back();
    }
    public function ios(Request $request){
        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }
        //ios
        $ios_min_version =  $request->ios_min_version;
        $ios_current_version =  $request->ios_current_version;
        $ios_update_required =  $request->ios_update_required;
        settings()->set("ios_min_version", $ios_min_version);
        settings()->set("ios_current_version", $ios_current_version);
        settings()->set("ios_update_required", $ios_update_required);
        return back();

    }
    public function android(Request $request){
        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }
        //android
        $android_min_version =  $request->android_min_version;
        $android_current_version =  $request->android_current_version;
        $android_update_required =  $request->android_update_required;
        settings()->set("android_min_version", $android_min_version);
        settings()->set("android_current_version", $android_current_version);
        settings()->set("android_update_required", $android_update_required);
        return back();

    }
    public function hawawi(Request $request){
        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }
        //huawei
        $huawei_min_version =  $request->huawei_min_version;
        $huawei_current_version =  $request->huawei_current_version;
        $huawei_update_required =  $request->huawei_update_required;
        settings()->set("huawei_min_version", $huawei_min_version);
        settings()->set("huawei_current_version", $huawei_current_version);
        settings()->set("huawei_update_required", $huawei_update_required);
        return Redirect::back();
        return back();

    }
    public function postAddSitin(Request $request)
    {
        if (!Admin::user()->can('*')){
            Permission::check('edit-'.$this->permission_name);
        }
        //chat configs
        $chat_status =  $request->chat_status;
        $chat_enable_version =  $request->chat_enable_version;
        $invitation_code_date =  $request->invitation_code_date;
        $show_welcom_enmation =  $request->show_welcom_enmation;
        settings()->set("chat_status", $chat_status);
      //  settings()->set("chat_enable_version", $chat_enable_version);
        settings()->set("invitation_code_date", $invitation_code_date);
       // settings()->set("show_welcom_enmation", $show_welcom_enmation);

        // Platform settings: admin sends versionName for min_version; map it back to
        // versionCode for storage. current_version is read-only and never written here.
        foreach (['android', 'ios', 'huawei'] as $os) {
            $minName = $request->input($os . '_min_version');
            $minCode = $this->resolveVersionCode($os, $minName);
            settings()->set($os . '_min_version', $minCode);

            settings()->set($os . '_update_required', $request->has($os . '_update_required') ? 1 : 0);
        }

        return Redirect::back();
    }

    /**
     * Resolve a submitted versionName back to its stored versionCode using the
     * {os}_version_names mapping. Falls back to the current stored code when the
     * name does not match (safe no-op).
     */
    private function resolveVersionCode(string $os, $name)
    {
        $current = settings()->get($os . '_min_version');

        if ($name === null || $name === '') {
            return $current;
        }

        $versionNames = json_decode(settings()->get($os . '_version_names') ?? '', true);
        if (!is_array($versionNames)) {
            return $current;
        }

        $code = array_search($name, $versionNames, true);

        return $code === false ? $current : $code;
    }

    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'chat_status'        => 'nullable|in:on,off',
            'invitation_code_date'        => 'nullable|numeric',
            'show_welcom_enmation'        => 'nullable|in:on,off',
            'android_min_version'         => 'nullable|numeric',
            'android_current_version'         => 'nullable|numeric',
            'android_update_required'         => 'nullable|numeric',
            'ios_min_version'         => 'nullable|numeric',
            'ios_current_version'         => 'nullable|numeric',
            'ios_update_required'         => 'nullable|numeric',
            'huawei_min_version'         => 'nullable|numeric',
            'huawei_current_version'         => 'nullable|numeric',
            'huawei_update_required'         => 'nullable|numeric',

        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.validation_error'), $validator->errors());
        }
        $chat_status =  $request->chat_status;
        $invitation_code_date =  $request->invitation_code_date;
        $show_welcom_enmation =  $request->show_welcom_enmation;
        settings()->set("chat_status", $chat_status);
        settings()->set("invitation_code_date", $invitation_code_date);
        settings()->set("show_welcom_enmation", $show_welcom_enmation);

        //android
        $android_min_version =  $request->android_min_version;
        $android_current_version =  $request->android_current_version;
        $android_update_required =  $request->android_update_required;
        settings()->set("android_min_version", $android_min_version);
        settings()->set("android_current_version", $android_current_version);
        settings()->set("android_update_required", $android_update_required);

        //ios
        $ios_min_version =  $request->ios_min_version;
        $ios_current_version =  $request->ios_current_version;
        $ios_update_required =  $request->ios_update_required;
        settings()->set("ios_min_version", $ios_min_version);
        settings()->set("ios_current_version", $ios_current_version);
        settings()->set("ios_update_required", $ios_update_required);

        //huawei
        $huawei_min_version =  $request->huawei_min_version;
        $huawei_current_version =  $request->huawei_current_version;
        $huawei_update_required =  $request->huawei_update_required;
        settings()->set("huawei_min_version", $huawei_min_version);
        settings()->set("huawei_current_version", $huawei_current_version);
        settings()->set("huawei_update_required", $huawei_update_required);
        return Common::apiResponse(1, 'created successfully');
    }

    public function show()
    {
        $android_min_version =  settings()->get('android_min_version');
        $android_current_version =  settings()->get('android_current_version');
        $android_update_required = settings()->get('android_update_required');


        $ios_min_version =  settings()->get('ios_min_version');
        $ios_current_version =  settings()->get('ios_current_version');
        $ios_update_required = settings()->get('ios_update_required');

        $huawei_min_version =  settings()->get('huawei_min_version');
        $huawei_current_version =  settings()->get('huawei_current_version');
        $huawei_update_required = settings()->get('huawei_update_required');

        $chat_status = settings()->get('chat_status');
        $invitation_code_date = settings()->get('invitation_code_date');
        $show_welcom_enmation = settings()->get('show_welcom_enmation');
        $data = [
            'android_min_version'  => $android_min_version,
            'android_current_version' =>  $android_current_version,
            'android_update_required' => $android_update_required,
            'ios_min_version' => $ios_min_version,
            'ios_current_version' => $ios_current_version,
            'ios_update_required' => $ios_update_required,
            'huawei_min_version' => $huawei_min_version,
            'huawei_current_version' => $huawei_current_version,
            'huawei_update_required' => $huawei_update_required,
            'chat_status' => $chat_status,
            'invitation_code_date' => $invitation_code_date,
            'show_welcom_enmation' => $show_welcom_enmation,
        ];
        return Common::apiResponse(1, '', $data);
    }
}
