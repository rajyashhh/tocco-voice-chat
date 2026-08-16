<?php

namespace App\ShippingAdmin\Controllers;

use App\Models\ShippingSuperAdmin;
use Encore\Admin\Controllers\AuthController as BaseAuthController;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use KevinSoft\MultiLanguage\MultiLanguage;

/**
 * Auth gate for the Shipping Super Admin portal. Mirrors App\Bd\Controllers\
 * AuthController: same laravel-admin session guard, gated on
 * type=shipping_super_admin only.
 */
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
        $shippingAdminLogin = 'shippingAdmin/login';

        if ($user && str_contains($uri, $shippingAdminLogin) && $user->type === ShippingSuperAdmin::TYPE) {
            return redirect('/shippingAdmin');
        }

        $test = request()->query('redirect_url');
        $languages = MultiLanguage::config('languages');
        $cookie_name = MultiLanguage::config('cookie-name', 'locale');

        $current = MultiLanguage::config('default');
        if (Cookie::has($cookie_name)) {
            $current = Cookie::get($cookie_name);
        }

        return view('shippingAdmin::auth.login', compact('languages', 'current', 'test'));
    }

    public function postLogin(Request $request)
    {
        $this->loginValidator($request->all())->validate();

        $credentials = $request->only([$this->username(), 'password']);
        $remember = $request->get('remember', false);

        if ($this->guard()->attempt($credentials, $remember)) {
            return $this->sendLoginResponse($request);
        }

        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    public function sendLoginResponse(Request $request)
    {
        admin_toastr(trans('admin.login_successful'));

        $request->session()->regenerate();
        $user = $this->guard()->user();

        if ($user && $user->type === ShippingSuperAdmin::TYPE) {
            return redirect()->route('shippingAdmin.home');
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withInput()->withErrors([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    public function logout(Request $request)
    {
        $this->getLogout($request);

        return redirect()->route('shippingAdmin.login');
    }

    public function getSetting(Content $content)
    {
        $form = $this->settingForm();
        $form->tools(function (Form\Tools $tools) {
            $tools->disableList();
            $tools->disableDelete();
            $tools->disableView();
        });

        return $content
            ->title(trans('admin.user_setting'))
            ->body($form->edit(Admin::user()->id));
    }

    public function putSetting()
    {
        if (request('password') != Admin::user()->getAuthPassword()) {
            DB::table('sessions')->where('user_id', Admin::user()->getAuthIdentifier())->delete();
        }

        parent::putSetting();

        return redirect(shippingAdmin_url('/'));
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

        $form->setAction(shippingAdmin_url('update-setting'));
        $form->ignore(['password_confirmation']);

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = Hash::make($form->password);
            }
        });

        $form->saved(function () {
            admin_toastr(trans('admin.update_succeeded'));

            return redirect(shippingAdmin_url('setting'));
        });

        return $form;
    }
}