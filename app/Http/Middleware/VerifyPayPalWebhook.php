<?php

namespace App\Http\Middleware;

use App\Helpers\LogHelper;
use App\Services\PayPalService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyPayPalWebhook extends PayPalService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get headers using Laravel's request object (works with all servers)
        $headers = array_change_key_case($request->headers->all(), CASE_UPPER);
        
        // Flatten the headers array (Laravel returns arrays for each header)
        $headers = array_map(function ($value) {
            return is_array($value) ? $value[0] : $value;
        }, $headers);

        // Get the JSON payload as array
        $payload = $request->json()->all();

        $verificationData = [
            'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'] ?? null,
            'cert_url'          => $headers['PAYPAL-CERT-URL'] ?? null,
            'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'] ?? null,
            'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'] ?? null,
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? null,
            'webhook_id'        => config('paypal.webhook_id'),
            'webhook_event'     => $payload,
        ];

        $response = Http::withToken(app(PayPalService::class)->getAccessToken())
            ->post(config('paypal.base_url') . '/v1/notifications/verify-webhook-signature', $verificationData);

        if ($response->json('verification_status') !== 'SUCCESS') {
            return response()->json(['status' => 'unauthorized'], 401);
        }

        return $next($request);
    }

}
