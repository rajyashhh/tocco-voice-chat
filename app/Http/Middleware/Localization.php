<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

            $langCode = $request->header('X-localization', 'en');
        
            $language = Cache::rememberForever("language_enabled_{$langCode}", function () use ($langCode) {
                return Language::where('code', $langCode)
                    ->where('is_enabled', true)
                    ->first();
            });
        
            if ($language) {
                app()->setLocale($language->code);
            }
        
            return $next($request);
        
    }
}
