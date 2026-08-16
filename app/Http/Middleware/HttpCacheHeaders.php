<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds a strong ETag + short private Cache-Control to safe, read-only GET
 * endpoints so weak devices can revalidate (304 Not Modified) and avoid
 * re-downloading unchanged per-user payloads.
 *
 * Octane-safe: no static/global state, everything is request-local.
 * A 304 short-circuits only the BODY — the route action still runs. So attach
 * this only where the action's writes (if any) are idempotent and safe to
 * repeat within the cache window (e.g. my-store's 30s-gated salary recalc).
 * Never attach it to endpoints with non-idempotent/un-gated writes.
 */
class HttpCacheHeaders
{
    /**
     * Short revalidation window. Chosen at 20s to match the existing
     * server-side response cache on the rooms-list endpoint
     * (RoomController::index caches for 20s) and the my-store salary-recalc
     * lock (UserService::myStore, 30s). 20s keeps perceived data fresh for an
     * interactive app while still collapsing rapid re-fetches on flaky
     * networks. must-revalidate forces the client to re-check once stale.
     */
    private const MAX_AGE = 20;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$request->isMethod('GET')) {
            return $response;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return $response;
        }

        // Respect any upstream no-store decision (e.g. DisableOctaneCaching).
        $existing = $response->headers->get('Cache-Control', '');
        if (str_contains($existing, 'no-store') || str_contains($existing, 'no-cache')) {
            return $response;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (!str_contains($contentType, 'json')) {
            return $response;
        }

        $etag = '"' . md5((string) $response->getContent()) . '"';
        $response->headers->set('ETag', $etag);
        $response->headers->set(
            'Cache-Control',
            'private, max-age=' . self::MAX_AGE . ', must-revalidate'
        );
        // Payloads vary by request language (X-localization) and, if gzip is ever
        // attached, by negotiated encoding — so a private cache keys correctly.
        $this->appendVary($response, 'X-localization');
        $this->appendVary($response, 'Accept-Encoding');

        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch !== null && $this->etagMatches($ifNoneMatch, $etag)) {
            $response->setNotModified(); // 304, drops the body, keeps ETag/Cache-Control
        }

        return $response;
    }

    /**
     * Append a field to the Vary header without duplicating an existing one.
     */
    private function appendVary(Response $response, string $field): void
    {
        $vary = (string) $response->headers->get('Vary', '');
        if (str_contains(strtolower($vary), strtolower($field))) {
            return;
        }
        $response->headers->set('Vary', $vary === '' ? $field : $vary . ', ' . $field);
    }

    /**
     * If-None-Match may be a comma-separated list and may carry a weak (W/) prefix.
     */
    private function etagMatches(string $ifNoneMatch, string $etag): bool
    {
        foreach (explode(',', $ifNoneMatch) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '*') {
                return true;
            }
            if (str_starts_with($candidate, 'W/')) {
                $candidate = substr($candidate, 2);
            }
            if ($candidate === $etag) {
                return true;
            }
        }

        return false;
    }
}
