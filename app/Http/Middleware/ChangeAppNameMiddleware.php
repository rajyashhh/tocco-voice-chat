<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Cache;
use Closure;
use Database\Seeders\config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChangeAppNameMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $setting = Setting::where('key', 'app_title')->first();
        Cache::put('app_title', $setting?->value);
        return $next($request);
    }
}
