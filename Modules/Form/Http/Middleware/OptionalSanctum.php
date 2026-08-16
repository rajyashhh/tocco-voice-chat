<?php

namespace  Modules\Form\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class OptionalSanctum
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            return $next($request);
        }

        $token = $request->input('token') ?? $request->query('token');

        if ($token) {
            $tokenRecord = PersonalAccessToken::findToken($token);

            if ($tokenRecord) {
                Auth::loginUsingId($tokenRecord->tokenable_id);
                return $next($request);
            }
        }

        return response()->view('Form::forms.invalid', [
            'message' => 'Access denied. Invalid token or not authenticated.',
        ], 403);
    }
}
