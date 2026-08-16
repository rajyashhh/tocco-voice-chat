<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyCodapayWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = [
            '54.251.135.133',
            '3.111.59.175',
            '3.110.250.201',
            '65.1.187.239',
            '54.193.247.199',
            '18.198.204.13',
            '3.121.251.156',
            '3.67.31.16',
            '52.76.150.186',
            '18.140.224.133',
            '13.213.84.158',
            '13.228.141.160',
            '54.151.121.97',
            '54.219.48.129',
        ];

        if (!in_array($request->ip(), $allowedIps)) {
            return response()->json(['error' => 'Unauthorized IP'], 403);
        }

        $required = ['TxnId', 'OrderId', 'TotalPrice', 'Checksum'];
        foreach ($required as $field) {
            if (!$request->has($field)) {
                return response()->json(['error' => "Missing required field: {$field}"], 400);
            }
        }

        $txnId = $request->get('TxnId');
        $orderId = $request->get('OrderId');
        $resultCode = $request->get('ResultCode');
        $checksum  = $request->get('Checksum');

        $secretKey = config('codapay.api_key');

        $computedChecksum = md5($txnId . $secretKey . $orderId . $resultCode);

        if ($computedChecksum !== $checksum) {
            return response()->json(['error' => 'Invalid checksum'], 403);
        }

        return $next($request);
    }
}
