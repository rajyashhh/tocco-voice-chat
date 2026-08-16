<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DisableOctaneCaching
{
    /**
     * Handle an incoming request.
     * This middleware disables caching for Octane to ensure fresh data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Clear cache before processing request
        Cache::flush();
        
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        
        // Process the request
        $response = $next($request);
        
        // Clear cache after processing
        Cache::flush();
        
        // Add headers to prevent caching
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        
        return $response;
    }
}
