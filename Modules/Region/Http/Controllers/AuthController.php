<?php


namespace Modules\Region\Http\Controllers;


use Exception;
use App\Models\Agent;
use Encore\Admin\Form;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Services\WhatsappOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use KevinSoft\MultiLanguage\MultiLanguage;
use Modules\Region\Entities\AreaManager;
use Encore\Admin\Controllers\AuthController as BaseAuthController;


class AuthController extends BaseAuthController
{

    public function locale()
    {
        $locale = Request::input('locale');
        $languages = MultiLanguage::config('languages');

        $cookie_name = MultiLanguage::config('cookie-name', 'locale');
        if (array_key_exists($locale, $languages)) {

            return response('ok')->cookie($cookie_name, $locale);
        }
    }

    public function showLoginForm()
    {
        $user = Admin::user();
        $uri = request()->path();

        $adminLogin = 'admin/login';
        $areaManagerLogin = 'areaManager/login';

        if ($user) {

            if (str_contains($uri, $areaManagerLogin) && $user->type === 'employee') {
                return redirect('/admin');
            }

            if (str_contains($uri, $areaManagerLogin) && $user->type === 'region') {
                return redirect('/areaManager');
            }
        }

        if ($uri === $adminLogin) {
            return view('admin.login');
        }


        $test = request()->query('redirect_url');
        $languages = MultiLanguage::config("languages");
        $cookie_name = MultiLanguage::config('cookie-name', 'locale');

        $current = MultiLanguage::config('default');
        if (Cookie::has($cookie_name)) {
            $current = Cookie::get($cookie_name);
        }
        return view("areaManager.auth.login", compact('languages', 'current', 'test'));
    }

    public function sendCodeWhatsapp(Request $request)
    {
        $auth = \App\Models\Admin::where('username', $request->username)/**->where('type', $request->type)*/->first();
        $phone =  $auth->phone_code . $auth->phone;
        try {
            (new WhatsappOtp())->sendOtpMessage($phone);
        } catch (Exception $exception) {

            return Common::apiResponse(0, $exception->getMessage(), null, 400);
        }
        return Common::apiResponse(true, __('messages.code_is_sent_to_your_phone'));
    }

    public function changePasswordView(Request $request)
    {

        $userName = $request->username;
 
        $test = request()->query('redirect_url');
        $languages = MultiLanguage::config("languages");
        $cookie_name = MultiLanguage::config('cookie-name', 'locale');

        $current = MultiLanguage::config('default');
        if (Cookie::has($cookie_name)) {
            $current = Cookie::get($cookie_name);
        }
        return view("areaManager.auth.password", compact('userName', 'current'));
    }

    public function verifyWhatsappCode(Request $request)
    {
        $username = $request->username;
        $code     = $request->code;
        $type = $request->type;

        $auth = \App\Models\Admin::where('username', $username)/**->where('type', $type)*/->first();
        if (!$auth) {
            return response()->json([
                'success' => false,
                'message' => __('User not found'),
            ], 404);
        }

        $whatsappOtpService = new WhatsappOtp();
        $phone = $auth->phone_code . $auth->phone;

        if (!$whatsappOtpService->isValidate($phone, $code)) {
            return response()->json([
                'success' => false,
                'message' => __('api_responses.invalid_code'),
            ], 422);
        }

        $whatsappOtpService->resetCodes($phone);

        $languages   = MultiLanguage::config("languages");
        $cookie_name = MultiLanguage::config('cookie-name', 'locale');
        $current     = MultiLanguage::config('default');
        if (Cookie::has($cookie_name)) {
            $current = Cookie::get($cookie_name);
        }

        $redirect = areaManager_url('change-password-view') . '?username=' . urlencode($username) /** . '&type=' . urlencode($type)*/;


        return response()->json([
            'success'  => true,
            'message'  => __('تم التحقق بنجاح'),
            'redirect' =>  $redirect
        ]);
    }

    public function changePassword(Request $request)
    {
        $auth = \App\Models\Admin::where('username', $request->username)/**->where('type', $request->type)*/->first();
        $auth->password = Hash::make($request->password);
        $auth->save();
        return redirect(areaManager_url('login'))
            ->with('success', __('Password changed successfully. Please login with your new password.'));
    }


    

    public function postLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            // 'type'     => 'required|string', 
        ]);

        $admin = DB::table('admin_users')
            ->where('username', $request->username)
            // ->where('type', $request->type)
            ->first();

        if (!$admin) {
            return back()->withInput()->withErrors([
                'username' => trans('admin.username_not_found'),
            ]);
        }

        if (!Hash::check($request->password, $admin->password)) {
            return back()->withInput()->withErrors([
                'password' => trans('admin.password_incorrect'),
            ]);
        }

        Auth::guard('admin')->loginUsingId($admin->id, $request->boolean('remember'));

        return $this->sendLoginResponse($request);
    }



    public function sendLoginResponse(Request $request)
    {
        admin_toastr(trans('admin.login_successful'));

        $request->session()->regenerate();

        $user = $this->guard()->user();

        if (!$user) {
            return back()->withInput()->withErrors([
                $this->username() => $this->getFailedLoginMessage(),
            ]);
        }

        switch ($user->type) {
            case 'region':
                return redirect()->route('areaManager.home');
            case 'sub_region':
                return $this->redirectSubAreaManager($user,$request);
                // return redirect()->route('areaManager.home');
            default:
                $this->guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withInput()->withErrors([
                    $this->username() => $this->getFailedLoginMessage(),
                ]);
        }
    }

    public function logout(Request $request)
    {
        $this->getLogout($request);
        return redirect(areaManager_url('login'));
    }

    public function putSetting()
    {
        if (\request('password') != Admin::user()->getAuthPassword()) {
            Agent::where("id", Admin::user()->id)->update([
                "remember_token" => null
            ]);
            DB::table('sessions')->where('user_id', Admin::user()->getAuthIdentifier())->delete();
        }
        parent::putSetting();

        return redirect(areaManager_url('/'));
    }

    public function getSetting(Content $content)
    {
        $form = $this->settingForm();
        $form->tools(
            function (Form\Tools $tools) {
                $tools->disableList();
                $tools->disableDelete();
                $tools->disableView();
            }
        );

        return $content
            ->title(trans('admin.user_setting'))
            ->body($form->edit(Admin::user()->id));
    }

    protected function settingForm()
    {

        $class = config('admin.database.users_model');

        $form = new Form(new $class());

        $form->display('username', trans('admin.username'));
        $form->text('name', trans('admin.name'))->rules('required');
        $form->image('avatar', trans('admin.avatar'));
        if (!(Auth::user()->username == 'demo')) {
            $form->password('password', trans('admin.password'))->rules('confirmed|required');
            $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required')
                ->default(function ($form) {
                    return $form->model()->password;
                });
        }

        $form->setAction(areaManager_url('update-setting'));

        $form->ignore(['password_confirmation']);

        $form->saving(function (Form $form) {
            if ($form->model()->password != $form->password && $form->model()->username == 'admin' || $form->model()->password_confirmation != $form->password_confirmation && $form->model()->username == 'admin') {
                $error = new MessageBag(
                    [
                        'title'   => 'forbidden',
                        'message' => 'you can not make change',
                    ]
                );
                return back()->with(compact('error'));
            }
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        $form->saved(function () {
            admin_toastr(trans('admin.update_succeeded'));

            return redirect(areaManager_url('setting'));
        });

        return $form;
    }

    public function send_whatsapp_code_preview(Request $request)
    {
        $username = $request->input('username');
        $type = $request->input('type');

        $user = AreaManager::where('username', $username)/**->where('type', $type)*/->first();

        if (! $user || ! $user->phone) {
            return response()->json([
                'status'  => false,
                'message' => 'المستخدم غير موجود أو ليس له رقم واتساب',
            ]);
        }

        $masked = substr($user->phone_code . $user->phone, 0, -5) . '***';

        return response()->json([
            'status'        => true,
            'masked_number' => $masked,
            'message'       => 'تم جلب بيانات الرقم بنجاح',
        ]);
    }

    protected function redirectSubAreaManager($user, $request)
    {
        $routesMap = [
            'dashboard'            => areaManager_url('dashboard'),
            'superadmin'           => areaManager_url('superadmin-users'),
            'coin-recharge'        => areaManager_url('charges'),
            'Bds'                  => areaManager_url('user-Bds'),
            'professional-bd'      => areaManager_url('professional-bd'),
            'agency'               => areaManager_url('agencies'),
            'shipping-agency'      => areaManager_url('charge-agencies'),
            'host'                 => areaManager_url('agency/users'),
            'professional-users'   => areaManager_url('agency/professional-users'),
            'rooms'                => areaManager_url('rooms'),
            'live-rooms'           => areaManager_url('live-rooms'),
            'official-messages'    => areaManager_url('official-message'),
            'roles'                => areaManager_url('roles'),
            'auth-users'           => areaManager_url('auth-users'),
        ];
        

        foreach ($routesMap as $permission => $url) {
            if ($this->hasPermission($permission)) {
                return redirect()->to($url);
            }
        }
    
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    
        return back()->withInput()->withErrors([
            $this->username() => 'ليس لديك صلاحيات للدخول.',
        ]);
    }
    

    protected function hasPermission($permission)
    {
        $user = Admin::user();

        if ($user->can('*')) {
            return true;
        }

        if (is_null($permission)) {
            return true;
        }

        return $user->can('browse-' . $permission);
    }


}
