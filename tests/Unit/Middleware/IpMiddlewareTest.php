<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\IpMiddleware;
use App\Jobs\TrackUserIpJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit Test: App\Http\Middleware\IpMiddleware
 *
 * Proves the read path no longer writes to the DB synchronously: the IP write
 * is deferred to TrackUserIpJob and gated by an atomic Cache::add so it is
 * dispatched at most once per (ip,uid) window (idempotency).
 */
class IpMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Queue::fake();
    }

    private function pass(Request $request): Response
    {
        return (new IpMiddleware())->handle($request, fn () => new Response('ok'));
    }

    public function test_dispatches_track_ip_job_once_per_window(): void
    {
        $request = Request::create('/api/anything', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.10');

        // First request in the window: job dispatched.
        $this->pass($request);
        Queue::assertPushed(TrackUserIpJob::class, 1);

        // Subsequent requests with the same (ip,uid): gate is closed, no dispatch.
        $this->pass($request);
        $this->pass($request);
        Queue::assertPushed(TrackUserIpJob::class, 1);
    }

    public function test_dispatches_again_for_a_different_ip(): void
    {
        $first = Request::create('/api/anything', 'GET');
        $first->server->set('REMOTE_ADDR', '203.0.113.10');

        $second = Request::create('/api/anything', 'GET');
        $second->server->set('REMOTE_ADDR', '203.0.113.99');

        $this->pass($first);
        $this->pass($second);

        // Distinct (ip,uid) gates => one dispatch each.
        Queue::assertPushed(TrackUserIpJob::class, 2);
    }

    public function test_job_dispatched_on_default_queue(): void
    {
        $request = Request::create('/api/anything', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.10');

        $this->pass($request);

        Queue::assertPushedOn('default', TrackUserIpJob::class);
    }

    public function test_request_continues_down_the_pipeline(): void
    {
        $request = Request::create('/api/anything', 'GET');
        $request->server->set('REMOTE_ADDR', '203.0.113.10');

        $response = $this->pass($request);

        $this->assertSame('ok', $response->getContent());
    }
}
