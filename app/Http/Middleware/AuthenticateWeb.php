<?php

namespace App\Http\Middleware;

use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Str;

class AuthenticateWeb
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        \config(['auth.defaults.guard' => 'admin']);
        $uri = $request->path();

        $user = Admin::user();
        // dd( $user);
        // Panel staff carry type='employee'. A NULL type is a legacy panel
        // account and is treated the same (the pre-unification default).
        $userType = $user?->type ?? 'employee';
        //  dd($userType);

        $adminLogin = 'admin/login';
        $bdLogin = 'bd/login';
        $superadminLogin = 'superadmin/login';
        $areaManagerLogin = 'areaManager/login';
        $shippingAdminLogin = 'shippingAdmin/login';

        if ($user) {
            if (Str::is($uri, $adminLogin) && $userType === 'employee') {
                return redirect('/admin');
            }
            if (Str::is($uri, $bdLogin) && $userType === 'bd') {
                return redirect('/bd');
            }
            if (
                Str::is($uri, $superadminLogin)
                && in_array($userType, ['country', 'sub_country'], true)
            ) {
                return redirect('/superadmin');
            }
           // dd($uri, $areaManagerLogin);
            if (
                Str::is($uri, $areaManagerLogin)
                && in_array($userType, ['region', 'sub_region'], true)
            ) {
                return redirect('/areaManager');
            }

            if (Str::is($uri, $shippingAdminLogin) && $userType === 'shipping_super_admin') {
                return redirect('/shippingAdmin');
            }

            if (
                (Str::startsWith($uri, 'bd') && $userType !== 'bd') ||
                (Str::startsWith($uri, 'superadmin') && $userType !== 'country'&& $userType !== 'sub_country') ||
                (Str::startsWith($uri, 'areaManager') && $userType !== 'region' && $userType !== 'sub_region') ||
                (Str::startsWith($uri, 'shippingAdmin') && $userType !== 'shipping_super_admin') ||
                (Str::startsWith($uri, 'admin') && $userType !== 'employee')
            ) {
                Admin::guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($userType === 'bd') {
                    return redirect('/bd/login')->withErrors(['error' => 'Please login through BD portal.']);
                } elseif ($userType === 'country') {
                    return redirect('/superadmin/login')->withErrors(['error' => 'Please login through country manager portal.']);
                } elseif ($userType === 'region') {
                    return redirect('/areaManager/login')->withErrors(['error' => 'Please login through Area Manager portal.']);
                } elseif ($userType === 'sub_region') {
                    return redirect('/areaManager/login')->withErrors(['error' => 'Please login through Area Manager portal.']);
                } elseif ($userType === 'sub_country') {
                    return redirect('/superadmin/login')->withErrors(['error' => 'Please login through country manager 1111 portal.']);
                } elseif ($userType === 'shipping_super_admin') {
                    return redirect('/shippingAdmin/login')->withErrors(['error' => 'Please login through Shipping Super Admin portal.']);
                } else {
                    return redirect('/admin/login')->withErrors(['error' => 'Please login through Admin portal.']);
                }
            }
        }

        $redirectTo = admin_base_path(config('admin.auth.redirect_to', 'auth/login'));
        $test = $request->getRequestUri();

        if (Str::contains($uri, 'bd')) {
            $redirectTo = '/bd/login';
        }
        if (Str::contains($uri, 'superadmin')) {
            $redirectTo = '/superadmin/login';
        }
        if (Str::contains($uri, 'areaManager')) {
            $redirectTo = '/areaManager/login';
        }
        if (Str::contains($uri, 'shippingAdmin')) {
            $redirectTo = '/shippingAdmin/login';
        }

        if (Admin::guard()->guest() && !$this->shouldPassThrough($request)) {
            return redirect()->to($redirectTo . '?redirect_url=' . urlencode($test));
        }

        return $next($request);
    }
    /**
     * Determine if the request has a URI that should pass through verification.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        // 下面的路由不验证登陆
        $excepts = config('admin.auth.excepts', []);

        array_delete($excepts, [
            '_handle_action_',
            '_handle_form_',
            '_handle_selectable_',
            '_handle_renderable_',
        ]);

        return collect($excepts)
            ->map('admin_base_path')
            ->contains(function ($except) use ($request) {
                if ($except !== '/') {
                    $except = trim($except, '/');
                }

                return $request->is($except);
            });
    }
}
