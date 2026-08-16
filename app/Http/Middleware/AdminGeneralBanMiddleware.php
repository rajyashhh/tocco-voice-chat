<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use App\Models\Ban;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminGeneralBanMiddleware
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
        $user = Auth::user ();
        $now = now();
        $is_user_ban = Ban::query ()
            ->where ('uid',$user->id)
            ->where ('user_type',1)
            ->whereRaw("created_at + INTERVAL duration DAY > '$now'")
            ->exists ();
        $ip_ban = Ban::query ()->where ('ip',$request->ip)
            ->whereRaw("created_at + INTERVAL duration DAY > '$now'")
            ->exists ();
        if ($is_user_ban || $ip_ban){
            Auth::logout ();
            return redirect (config('admin.route.prefix'));
        }
        return $next($request);
    }
}
