<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to disable Pjax (Push State + AJAX) for Octane/Swoole compatibility
 * 
 * Laravel Admin's Pjax middleware uses exit() which is incompatible with Swoole.
 * This middleware removes the X-PJAX header when running under Octane, forcing
 * full page reloads instead of AJAX partial updates.
 */
class DisablePjaxForOctane
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if running under Swoole/Octane
        if (function_exists('swoole_version')) {
            // Remove the X-PJAX header to disable Pjax functionality
            // This prevents the Pjax middleware from calling exit()
            $request->headers->remove('X-PJAX');
        }

        return $next($request);
    }
}
