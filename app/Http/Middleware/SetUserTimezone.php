<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stevebauman\Location\Facades\Location;

class SetUserTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect the user's time zone based on their IP
        // Fallback to UTC if the time zone is not detected
        $timezone = $request->header('tz') ?? 'UTC';


        // Store the time zone in the session
        session(['user_timezone' => $timezone]);

        return $next($request);
    }
}
