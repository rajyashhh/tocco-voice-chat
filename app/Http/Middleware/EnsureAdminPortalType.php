<?php

namespace App\Http\Middleware;

use Closure;
use Encore\Admin\Facades\Admin;

class EnsureAdminPortalType
{
    /**
     * Enforce, on every request, that the authenticated admin_users session
     * carries a type allowed for this portal. The admin.auth middleware only
     * guarantees an authenticated session; the type is otherwise validated
     * once at login. Without this, any valid admin_users session crosses into
     * every portal. The allowed types passed here must mirror the portal's
     * login allow-list exactly.
     */
    public function handle($request, Closure $next, ...$allowedTypes)
    {
        $user = Admin::user();

        if (!$user || !in_array($user->type, $allowedTypes, true)) {
            abort(403);
        }

        return $next($request);
    }
}
