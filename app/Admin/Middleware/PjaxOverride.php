<?php

namespace App\Admin\Middleware;

use Encore\Admin\Middleware\Pjax as BasePjax;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Exceptions\HttpResponseException;

class PjaxOverride extends BasePjax
{
    /**
     * Send a response through this middleware.
     * Override to avoid using exit() which breaks Swoole/Octane
     * Uses HttpResponseException instead of exit to stop execution
     *
     * @param Response $response
     * @throws HttpResponseException
     */
    public static function respond(Response $response)
    {
        $next = function () use ($response) {
            return $response;
        };

        $result = (new static())->handle(app('request'), $next);

        // Throw HttpResponseException instead of exit()
        // Laravel will catch this and send the response properly
        throw new HttpResponseException($result);
    }
}
