<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserBan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $route = null)
    {
        $userId = auth()->id();
        $method = $request->method();
        $routePath = $route ; 

        if (Common::isUserBannedFromRoute($userId, $routePath, $method)) {
            return Common::apiResponse(1, __('banned_from_action'),[],377 );
        }
    
        return $next($request);
    }
}
