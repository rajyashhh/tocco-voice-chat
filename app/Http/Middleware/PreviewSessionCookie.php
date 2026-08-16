<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pick the session cookie name PER REQUEST so the role "preview" feature can't
 * corrupt the real admin's session.
 *
 * The preview feature logs in a throwaway view-only user under a separate cookie
 * (laravel_preview). That isolation used to be set in ConfigServiceProvider::boot()
 * by request path — but under Octane a provider boots ONCE per long-lived worker,
 * not per request, so whichever request first warmed the worker decided the cookie
 * for ALL its later requests: a worker that served a /preview request then served
 * /admin requests on the PREVIEW session, silently flipping the real admin's tab
 * to the preview (view-only) user.
 *
 * This middleware runs first in the `web` group (before StartSession reads the
 * cookie name), so the choice is made fresh on every request and can never leak
 * across requests: /preview/* -> laravel_preview, everything else -> the app's
 * default session cookie.
 */
class PreviewSessionCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $isPreview = Str::startsWith(ltrim($request->path(), '/'), 'preview');

        config([
            'session.cookie' => $isPreview
                ? 'laravel_preview'
                : Str::slug(env('APP_NAME', 'laravel'), '_') . '_session',
        ]);

        return $next($request);
    }
}
