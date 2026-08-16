<?php


namespace Modules\Country\Http\Controllers\SuperAdmin;

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
use Modules\Country\Entities\SuperAdmin;
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
        $superadminLogin = 'superadmin/login';

        if ($user) {

            if (str_contains($uri, $superadminLogin) && $user->type === 'employee') {
                return redirect('/admin');
            }

            if (str_contains($uri, $superadminLogin) && $user->type === 'country') {
                return redirect('/superadmin');
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
        return view("SuperAdmin::auth.login", compact('languages', 'current', 'test'));
    }

    public function sendCodeWhatsapp(Request $request)
    {
        $auth = \App\Models\Admin::where('username', $request->username)->where('type', $request->type)->first();
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
        $type = $request->type;
        // $auth = \App\Models\Admin::where('username', $request->username)->first();
        // if (!$auth) {
        //     return back()->withErrors(['username' => __('User not found')]);
        // }
        // $whatsappOtpService = new WhatsappOtp();
        // $phone              = $auth->phone_code . $auth->phone;
        // $isValid            = $whatsappOtpService->isValidate($phone, $request->code);
        // if (!$isValid) {
        //     return back()->withErrors(['code' => __('api_responses.invalid_code')])->withInput();
        // }
        // $whatsappOtpService->resetCodes($phone);
        $test = request()->query('redirect_url');
        $languages = MultiLanguage::config("languages");
        $cookie_name = MultiLanguage::config('cookie-name', 'locale');

        $current = MultiLanguage::config('default');
        if (Cookie::has($cookie_name)) {
            $current = Cookie::get($cookie_name);
        }
        return view("SuperAdmin::auth.password", compact('userName', 'current'));
    }

    public function verifyWhatsappCode(Request $request)
    {
        $username = $request->username;
        $code     = $request->code;
        $type = $request->type;
        $auth = \App\Models\Admin::where('username', $username)->where('type', $type)->first();
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

        $redirect = superadmin_url('change-password-view') . '?username=' . urlencode($username) . '&type=' . urlencode($type);


        return response()->json([
            'success'  => true,
            'message'  => __('تم التحقق بنجاح'),
            'redirect' =>  $redirect
        ]);
    }

    public function changePassword(Request $request)
    {
        //  dd(123,$request->username);
        $auth = \App\Models\Admin::where('username', $request->username)->where('type', $request->type)->first();
        $auth->password = Hash::make($request->password);
        $auth->save();
        return redirect(superadmin_url('login'))

            ->with('success', __('Password changed successfully. Please login with your new password.'));
        return redirect()->route('admin.login')->with('success', __('Password changed successfully'));
    }


    // public function postLogin(Request $request)
    // {
    //     $url = $request->url;

    //     $this->loginValidator($request->all())->validate();
    //     $admin = DB::table('admin_users')
    //         ->where('username', request('username'))
    //         ->where('type', request('type'))
    //         ->exists();

    //     if ($admin) {
    //         $credentials = $request->only([$this->username(), 'password']);
    //         $remember = $request->get('remember', false);

    //         if ($this->guard()->attempt($credentials, $remember)) {

    //             return $this->sendLoginResponse($request);
    //         }
    //     }
    //     return back()->withInput()->withErrors([
    //         dd(123),
    //         $this->username() => $this->getFailedLoginMessage(),
    //     ]);
    // }

    public function postLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            // 'type'     => 'required|string', // example: superadmin or sub_super_admin
        ]);

        // Fetch admin user by username and type
        $admin = DB::table('admin_users')
            ->where('username', $request->username)
            // ->where('type', $request->type)
            ->first();

        if (!$admin) {
            return back()->withInput()->withErrors([
                'username' => trans('admin.username_not_found'),
            ]);
        }

        // Check password manually
        if (!Hash::check($request->password, $admin->password)) {
            return back()->withInput()->withErrors([
                'password' => trans('admin.password_incorrect'),
            ]);
        }

        // Login manually via Auth guard
        Auth::guard('admin')->loginUsingId($admin->id, $request->boolean('remember'));

        // Successful login response
        return $this->sendLoginResponse($request);
    }

    public function sendLoginResponse(Request $request)
    {
        admin_toastr(trans('admin.login_successful'));

        $request->session()->regenerate();

        $user = Auth::guard('admin')->user();

        if (!$user) {
            return back()->withInput()->withErrors([
                $this->username() => $this->getFailedLoginMessage(),
            ]);
        }

        switch ($user->type) {
            case 'country':
                return redirect()->route('superadmin.home');
            case 'sub_country':
                return redirect()->route('superadmin.home');
            default:
                Auth::guard('admin')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withInput()->withErrors([
                    $this->username() => $this->getFailedLoginMessage(),
                ]);
        }
    }


    // public function sendLoginResponse(Request $request)
    // {
    //     admin_toastr(trans('admin.login_successful'));

    //     $request->session()->regenerate();

    //     $user = $this->guard()->user();


    //     if (!$user) {
    //         return back()->withInput()->withErrors([
    //             $this->username() => $this->getFailedLoginMessage(),
    //         ]);
    //     }
    //     // dd($user->type);
    //     switch ($user->type) {

    //         case 'superadmin':
    //             return redirect()->route('superadmin.home');
    //         case 'sub_super_admin':
    //             return redirect()->route('superadmin.home');
    //         default:
    //             $this->guard()->logout();
    //             $request->session()->invalidate();
    //             $request->session()->regenerateToken();
    //             return back()->withInput()->withErrors([
    //                 $this->username() => $this->getFailedLoginMessage(),
    //             ]);
    //     }
    // }

    public function logout(Request $request)
    {

        $this->getLogout($request);
        return redirect(superadmin_url('login'));
    }

      public function customSuperadminLogout(Request $request)
    {
        $this->getLogout($request);
        return redirect('/superadmin/login');
    }

        public function getLogout(Request $request)
    {
        $this->guard()->logout();
        return redirect(config('admin.superadmin_route.prefix'));
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

        return redirect(superadmin_url('/'));
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

        $this->addPhoneFields($form, 'sometimes');

        $form->setAction(superadmin_url('update-setting'));

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

            return redirect(superadmin_url('setting'));
        });

        return $form;
    }

    public function send_whatsapp_code_preview(Request $request)
    {
        $username = $request->input('username');
        $type = $request->input('type');

        $user = SuperAdmin::where('username', $username)->where('type', $type)->first();

        if (! $user || ! $user->phone) {
            return response()->json([
                'status'  => false,
                'message' => __('The user does not exist or does not have a WhatsApp number'),
            ]);
        }

        $masked = substr($user->phone_code . $user->phone, 0, -5) . '***';

        return response()->json([
            'status'        => true,
            'masked_number' => $masked,
            'message'       => __('The number data has been retrieved successfully'),
        ]);
    }

    protected function addPhoneFields(Form $form, $rules = 'required')
    {

        $form->text('phone', __('whatsApp number'))
            ->rules($rules)
            ->attribute('id', 'phone-input')
            ->attribute('maxlength', 12)
            ->default(function ($form) {
                if ($form->model()->phone && $form->model()->phone_code) {
                    return $form->model()->phone;
                }
                return null;
            });

        $form->hidden('phone_code')->default(function ($form) {
            return $form->model()->phone_code ?? '';
        });


        Admin::script($this->phoneJs());
    }

    protected function phoneJs()
    {
        return <<<JS
            function initPhoneInputById(inputId, hiddenId) {
                const input = document.querySelector(inputId);
                const hidden = document.querySelector(hiddenId);
                if (!input || input.classList.contains('iti-initialized')) return;

                const iti = window.intlTelInput(input, {separateDialCode: true, preferredCountries: ["eg"], utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"});
                input.classList.add('iti-initialized');

                if (input.value && hidden && hidden.value) iti.setNumber(hidden.value + input.value);

                input.addEventListener("countrychange", function () { if(hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode; });
                const form = input.closest('form');
                if(form && !form.classList.contains('phone-init')){
                    form.addEventListener('submit', function(){
                        // if(hidden) hidden.value = "+" + iti.getSelectedCountryData().dialCode;
                        // input.value = iti.getNumber(intlTelInputUtils.numberFormat.E164);
                                hidden.value = "+" + iti.getSelectedCountryData().dialCode;

                    });
                    form.classList.add('phone-init');
        }
    }

    function initAllPhones() { initPhoneInputById("#phone-input", "input[name='phone_code']"); }
    initAllPhones();
    $(document).on('pjax:complete', function () { setTimeout(initAllPhones, 100); });
    JS;
    }
}
