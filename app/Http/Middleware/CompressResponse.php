<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Opt-in application-level gzip fallback for read-only JSON endpoints.
 *
 * Best practice is to compress at the edge (nginx). The repo's nginx config is
 * not tracked here, so this is a deliberate fallback that is OFF by default and
 * enabled only via config('app.http_gzip') / HTTP_GZIP=true. When the edge
 * already compresses, leave this disabled so the app does not double-work.
 *
 * Octane-safe: no static/global state, all request-local.
 */
class CompressResponse
{
    private const MIN_LENGTH = 1024; // 1KB — below this, gzip overhead is not worth it.

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!config('app.http_gzip', false)) {
            return $response;
        }

        if (!function_exists('gzencode')) {
            return $response;
        }

        $acceptEncoding = (string) $request->headers->get('Accept-Encoding', '');
        if (!str_contains($acceptEncoding, 'gzip')) {
            return $response;
        }

        // Never double-compress.
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json') && !str_contains($contentType, 'text/')) {
            return $response;
        }

        $body = (string) $response->getContent();
        if (strlen($body) < self::MIN_LENGTH) {
            return $response;
        }

        $compressed = gzencode($body, 6);
        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));

        // Ensure caches/CDNs key on the negotiated encoding.
        $vary = (string) $response->headers->get('Vary', '');
        if (!str_contains(strtolower($vary), 'accept-encoding')) {
            $response->headers->set('Vary', trim($vary === '' ? 'Accept-Encoding' : $vary . ', Accept-Encoding'));
        }

        return $response;
    }
}
