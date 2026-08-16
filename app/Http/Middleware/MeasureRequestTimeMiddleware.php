<?php

namespace App\Http\Middleware;

use App\Helpers\LogHelper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class MeasureRequestTimeMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $end = microtime(true);
        $duration = $end - $start; 


        // LogHelper::info('Request timing', [
        //     'url' => $request->fullUrl(),
        //     'method' => $request->method(),
        //     'duration_seconds' => $duration,
        //     'body' => $request->all(),
        //     'response_body' => method_exists($response,'getContent') ? json_decode($response->getContent(), true) : null,
        // ]);

        return $response;
    }
}
