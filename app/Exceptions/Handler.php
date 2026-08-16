<?php

namespace App\Exceptions;

use App\Helpers\Common;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,

    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    // Render method to handle all exceptions
    public function render($request, Throwable $e): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        // Any exception that already carries a fully-built response (throttle
        // RateLimiter ->response(), abort_if($cond, $response), explicit
        // ->response() closures, etc.) is wrapped by Laravel in an
        // HttpResponseException. This custom handler does not extend HttpException,
        // so without this early-return it would fall through to the generic api/*
        // 500 branch and discard the prepared response (e.g. the lucky-gift
        // rate-limit 429 + Arabic message turned into an empty-body 500). Returning
        // the carried response restores Laravel's default, correct behavior.
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if ($request->is('api/*')) {
            if ($e instanceof CValidationException) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 422;
                return Common::apiResponse(0, $e->getMessage(), null, $statusCode);
            } elseif ($e instanceof ValidationException) {
                return Common::apiResponse(false, $e->getMessage(), $e->errors(), 422);
            }elseif ($e instanceof AuthenticationException) {

                return Common::apiResponse (false,'Unauthenticated',[],401);
            } elseif ($e instanceof ModelNotFoundException) {

                return Common::apiResponse (false,'Wrong passed data',[],422);
            }

            if ($e instanceof HttpException) {
                $statusCode = $e->getStatusCode();

                if ($statusCode < 100 || $statusCode > 599) {
                    $statusCode = 500;
                }

                // Handle rate limiting (429) with a clear message
                if ($statusCode === 429) {
                    $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
                    $data = $retryAfter ? ['retry_after' => $retryAfter] : null;
                    return Common::apiResponse(false, __('api_responses.too_many_requests'), $data, 429);
                }

                return Common::apiResponse(false, $e->getMessage(), null, $statusCode);
            }

            // Unhandled exception on an api/* route => an actual 500. The classified
            // ERROR line is emitted by the reportable() callback in register() (the
            // single canonical report path, so it fires exactly once per exception
            // and never double-logs). Here we only sanitize the client-facing body:
            // hand back a generic, safe message instead of the raw exception text,
            // which previously leaked SQL fragments, file paths and class names to
            // clients in production. This is what used to hide Chat-Message 500s.
            $message = config('app.debug')
                ? $e->getMessage()
                : __('api_responses.something_went_wrong');

            return Common::apiResponse(0, $message, null, 500);

        }
        // Handle validation exceptions


        return parent::render($request, $e);
    }

    /**
     * Expected api/* exceptions that render() already maps to a clean 4xx response
     * (validation, authentication, model-not-found, and explicit HttpExceptions).
     * These are not 500s and must not be logged as unhandled errors.
     */
    private function isExpectedApiException(Throwable $e): bool
    {
        return $e instanceof CValidationException
            || $e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof ModelNotFoundException
            || $e instanceof HttpException;
    }

    /**
     * Emit a single classified ERROR line for an unhandled api/* exception so the
     * 500 is never silent. Carries the correlation id (X-Request-Id set by the
     * RequestId middleware), the path, exception class, the driver errno for
     * database failures, and the authenticated user id when available — exactly the
     * fields needed to tie a client-facing 500 back to its root cause.
     */
    private function logApiException($request, Throwable $e): void
    {
        $errno = null;
        if ($e instanceof QueryException) {
            $errno = $e->errorInfo[1] ?? ($e->errorInfo[0] ?? null);
        }

        Log::error('Unhandled API exception', [
            'msg' => $e->getMessage(),
            'class' => get_class($e),
            'errno' => $errno,
            'path' => $request->path(),
            'request_id' => $request->header('X-Request-Id'),
            'user_id' => optional($request->user())->id,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Guarantee every unhandled api/* Throwable that becomes a 500 produces one
        // classified ERROR line. Returning false stops Laravel's default reporting
        // so the exception is logged exactly once (here) with full correlation
        // context, instead of either vanishing (the Chat-Message 500-without-ERROR
        // symptom) or being double-logged. Expected exceptions that render() already
        // translates to a clean 4xx (validation, auth, model-not-found, explicit
        // HttpException) are NOT 500s, so we skip them to avoid logging noise.
        // Non-api exceptions fall through to default reporting.
        $this->reportable(function (Throwable $e) {
            if (app()->runningInConsole()) {
                return;
            }

            try {
                $request = request();
            } catch (\Throwable) {
                return;
            }

            if (! $request || ! $request->is('api/*')) {
                return;
            }

            if ($this->isExpectedApiException($e)) {
                return;
            }

            $this->logApiException($request, $e);

            return false;
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return Common::apiResponse (false,'Unauthenticated',[],401);
            }
        });
    }
}
