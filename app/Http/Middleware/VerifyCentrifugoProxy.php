<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the Centrifugo subscribe-proxy call (plan section 4.2c).
 *
 * The subscribe proxy is a SERVER-TO-SERVER request: the Centrifugo node — not a
 * mobile client — POSTs to /centrifugo/subscribe when a user tries to subscribe
 * to a `groups:room.{id}` channel, so Laravel can authorize live membership. The
 * node is configured to send a shared secret on every such call; this middleware
 * verifies it with a timing-safe comparison before the controller runs. Without
 * this, any client could forge a membership-grant response.
 *
 * Returns the proxy error envelope Centrifugo expects (HTTP 200 with an `error`
 * object) on auth failure rather than a bare 401, so a misconfiguration surfaces
 * as a clean permission-denied at the node instead of a transport error.
 */
class VerifyCentrifugoProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('centrifugo.proxy_secret', '');
        $header     = (string) config('centrifugo.proxy_secret_header', 'X-Centrifugo-Proxy-Secret');

        // Refuse to run an unauthenticated proxy if the secret is not configured —
        // failing closed is the only safe default for a membership gate.
        if ($configured === '') {
            Log::error('VerifyCentrifugoProxy.misconfigured', [
                'reason' => 'CENTRIFUGO_PROXY_SECRET is not set',
            ]);

            return $this->denied();
        }

        $presented = (string) $request->header($header, '');

        if ($presented === '' || ! hash_equals($configured, $presented)) {
            Log::warning('VerifyCentrifugoProxy.invalid_secret', [
                'ip' => $request->ip(),
            ]);

            return $this->denied();
        }

        return $next($request);
    }

    /**
     * The proxy permission-denied envelope (Centrifugo proxy contract).
     */
    private function denied(): Response
    {
        return response()->json([
            'error' => [
                'code'    => 403,
                'message' => 'permission denied',
            ],
        ]);
    }
}
