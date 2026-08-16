<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

use function Laravel\Prompts\info;

class VerifyPayMobSignature
{
    private array $hmacKeys = [
        'amount_cents',
        'created_at',
        'currency',
        'error_occured',
        'has_parent_transaction',
        'id',
        'integration_id',
        'is_3d_secure',
        'is_auth',
        'is_capture',
        'is_refunded',
        'is_standalone_payment',
        'is_voided',
        'order',
        'owner',
        'pending',
        'source_data_pan',
        'source_data_sub_type',
        'source_data_type',
        'success',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $hmacSecret = config('services.paymob.hmac_secret');

        if ($request->isMethod('post')) {
            $data = $request->input('obj', []);
            $receivedHmac = $request->query('hmac') ?? $request->input('hmac');

            $data['source_data_pan'] = $data['source_data']['pan'] ?? '';
            $data['source_data_sub_type'] = $data['source_data']['sub_type'] ?? '';
            $data['source_data_type'] = $data['source_data']['type'] ?? '';
            $data['order'] = $data['order']['id'] ?? $data['order'] ?? '';
        } else {
            $data = $request->all();
            $receivedHmac = $request->query('hmac');

            $data['source_data_pan'] = $data['source_data_pan'] ?? $data['source_data.pan'] ?? '';
            $data['source_data_sub_type'] = $data['source_data_sub_type'] ?? $data['source_data.sub_type'] ?? '';
            $data['source_data_type'] = $data['source_data_type'] ?? $data['source_data.type'] ?? '';
        }

        if (!$receivedHmac) {
            Log::warning('PayMob: Missing HMAC signature');
            return response()->json(['error' => 'Missing signature'], 403);
        }

        $hmacString = '';
        foreach ($this->hmacKeys as $key) {
            $value = $data[$key] ?? '';
            // Convert boolean to string
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $hmacString .= $value;
        }

        $calculatedHmac = hash_hmac('sha512', $hmacString, $hmacSecret);

        if (!hash_equals($calculatedHmac, $receivedHmac)) {
            Log::warning('PayMob: Invalid HMAC signature', [
                'received' => $receivedHmac,
                'calculated' => $calculatedHmac,
                'hmac_string' => $hmacString,
                'data' => $data,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        return $next($request);
    }
}
