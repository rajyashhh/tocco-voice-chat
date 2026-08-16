<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the NowPayments IPN signature.
 *
 * NowPayments signs each IPN with HMAC-SHA512 of the request body serialized as
 * JSON with its keys sorted alphabetically (recursively), keyed by the merchant
 * IPN secret. The digest is sent in the `x-nowpayments-sig` header. Without this
 * check the callback trusts the caller-supplied `payment_status`, allowing anyone
 * to mark an order paid.
 */
class VerifyNowPaymentsSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('x-nowpayments-sig');

        if (!$signature) {
            Log::warning('NowPayments IPN: missing signature header', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Missing signature'], 401);
        }

        $secret = config('services.now_payments.ipn_secret');

        if (!$secret) {
            Log::error('NowPayments IPN: secret not configured');
            return response()->json(['message' => 'Webhook not configured'], 500);
        }

        $params = $request->all();
        $this->ksortRecursive($params);
        $sorted = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $expected = hash_hmac('sha512', $sorted, $secret);

        if (!hash_equals($expected, $signature)) {
            Log::warning('NowPayments IPN: invalid signature', [
                'ip' => $request->ip(),
                'payment_id' => $request->input('payment_id'),
            ]);
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }

    private function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }
}
