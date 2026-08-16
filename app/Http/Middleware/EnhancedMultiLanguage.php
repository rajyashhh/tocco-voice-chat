<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EnhancedMultiLanguage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $cookieName = 'locale'; // Use fixed cookie name since we're not using MultiLanguage config
        $languages = Cache::get('languages', [
            'ar' => 'العربية', 
            'en' => 'English', 
            'tr' => 'Turkish', 
            'hi' => 'Indian'
        ]);
        
        // Priority order for locale detection:
        // 1. URL parameter (for new tabs/windows)
        // 2. Custom header (for AJAX requests)
        // 3. Existing cookie
        // 4. Session
        // 5. Default application locale
        
        $locale = null;
        $source = 'default';
        
        // Check URL parameter first (highest priority for new tabs)
        if ($request->has('locale') && array_key_exists($request->input('locale'), $languages)) {
            $locale = $request->input('locale');
            $source = 'url_parameter';
            
            // Set cookie for future requests
            cookie()->queue($cookieName, $locale, 525600); // 1 year
        }
        // Check custom header (for AJAX requests)
        elseif ($request->hasHeader('X-Locale') && array_key_exists($request->header('X-Locale'), $languages)) {
            $locale = $request->header('X-Locale');
            $source = 'header';
            
            // Set cookie for future requests
            cookie()->queue($cookieName, $locale, 525600); // 1 year
        }
        // Check existing cookie
        elseif ($request->cookie($cookieName) && array_key_exists($request->cookie($cookieName), $languages)) {
            $locale = $request->cookie($cookieName);
            $source = 'cookie';
        }
        // Check session
        elseif ($request->session()->has($cookieName) && array_key_exists($request->session()->get($cookieName), $languages)) {
            $locale = $request->session()->get($cookieName);
            $source = 'session';
            
            // Move to cookie for better persistence
            cookie()->queue($cookieName, $locale, 525600); // 1 year
        }

        // No explicit source (fresh browser, no cookie/session/header): fall back
        // to Arabic. This middleware runs ONLY on the admin/superadmin/areaManager/
        // agency route groups (config/admin.php), which are Arabic-first (the login
        // page is already hardcoded ar/rtl). Without this the panel rendered LTR on
        // a clean install because getLocale() stayed on the framework default 'en'
        // and the RTL <html dir> gate only flips for ar/fa/ur/he. Scoped here so the
        // API's own locale resolution (and its 'en' default) is untouched.
        if (!$locale) {
            $locale = 'ar';
            $source = 'admin_default';
        }

        // Set locale if found
        if ($locale) {
            app()->setLocale($locale);
            
            // Store in session for backup
            session([$cookieName => $locale]);
            
            // Log for debugging (only in local environment)
            if (app()->environment('local')) {
            }
        }
        
        return $next($request);
    }
}
