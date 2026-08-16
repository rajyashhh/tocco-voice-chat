<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyUtdStreamWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // The UTD Stream callbackDispatcher signs with X-UTD-Stream-Signature
        // (sha256=<hmac>); the old X-UTD-Voice-Signature name is kept as a
        // legacy fallback.
        $signature = $request->header('X-UTD-Stream-Signature')
            ?? $request->header('X-UTD-Voice-Signature');

        if (!$signature) {
            Log::warning('UTD-Stream webhook: Missing signature header');
            return response()->json(['message' => 'Missing signature'], 401);
        }

        // Get callback secret from config
        $callbackSecret = Common::getConfig('utd_stream_callback_secret');

        if (!$callbackSecret) {
            Log::error('UTD-Stream webhook: Callback secret not configured');
            return response()->json(['message' => 'Webhook not configured'], 500);
        }

        // Verify signature
        if (!$this->verifySignature($request, $callbackSecret, $signature)) {
            Log::warning('UTD-Stream webhook: Invalid signature', [
                'ip' => $request->ip(),
                'event' => $request->input('event'),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    /**
     * Verify webhook signature using HMAC-SHA256
     *
     * @param Request $request
     * @param string $secret
     * @param string $signature
     * @return bool
     */
    private function verifySignature(Request $request, string $secret, string $signature): bool
    {
        // Get raw request body
        $payload = $request->getContent();

        // Calculate expected signature
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        // Timing-safe comparison
        return hash_equals($expectedSignature, $signature);
    }
}
