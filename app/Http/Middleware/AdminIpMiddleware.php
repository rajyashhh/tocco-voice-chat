<?php

namespace App\Http\Middleware;

use App\Models\Ip;
use Closure;
use Illuminate\Http\Request;

class AdminIpMiddleware
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
        $user = $request->user ();
        Ip::query ()->updateOrCreate (
            [
                'ip'=>$request->ip (),
                'uid'=>@$user->id,
            ],
            [
                'ip'=>$request->ip (),
                'uid'=>@$user->id,
                'user_type'=>1
            ]
        );
        return $next($request);
    }
}
