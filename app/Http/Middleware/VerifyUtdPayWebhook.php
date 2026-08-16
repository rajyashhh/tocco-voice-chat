<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyUtdPayWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('utd')->info('webhook middleware hit', ['ip' => $request->ip()]);
        $secret = config('utd.webhook_secret');

        if (empty($secret)) {
            Log::channel('utd')->error('webhook secret not configured');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $signature = $request->header('X-UTD-Signature');
        $timestamp = $request->header('X-UTD-Timestamp');

        if (!$signature || !$timestamp) {
            Log::channel('utd')->warning('missing signature headers', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            Log::channel('utd')->warning('signature timestamp expired', ['timestamp' => $timestamp]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $apiKey = $request->header('X-UTD-Api-Key', '');
        $projectId = $request->header('X-UTD-Project-Id', '');

        $signData = $timestamp . '.' . $apiKey . '.' . $projectId . '.' . $request->getContent();
        $expectedSignature = hash_hmac('sha256', $signData, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::channel('utd')->warning('invalid signature', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        Log::channel('utd')->info('webhook signature verified', ['projectId' => $projectId]);
        return $next($request);
    }
}
