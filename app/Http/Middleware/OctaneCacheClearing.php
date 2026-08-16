<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OctaneCacheClearing
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For admin sorting operations
        if ($request->is('admin/*') && $request->method() === 'POST') {
            // Clear cache tags
           // \Illuminate\Support\Facades\Cache::tags(['gift_categories', 'admin_data'])->flush();
        }

        $response = $next($request);

        return $response;
    }
}
