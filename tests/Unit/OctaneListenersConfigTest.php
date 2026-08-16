<?php

namespace Tests\Unit;

use App\Listeners\DisconnectIdleDbConnections;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Listeners\FlushLogContext;
use Laravel\Octane\Listeners\GiveNewRequestInstanceToApplication;
use Laravel\Octane\Octane;
use PHPUnit\Framework\TestCase;

/**
 * Guards the Octane listener configuration against the shallow-merge trap.
 *
 * mergeConfigFrom does a shallow array_merge on the top-level `octane` key, so
 * defining the `listeners` key in config/octane.php REPLACES the package default
 * wholesale (it is NOT deep-merged). A previous partial list defined only
 * RequestTerminated, which silently dropped FlushLogContext — leaking the
 * request_id set via Log::withContext from the RequestId middleware across
 * requests on the same Swoole worker and corrupting the correlation id.
 *
 * This test pins the contract: the per-request reset listeners (FlushLogContext
 * and the request-instance listeners) MUST remain present under RequestReceived,
 * while our DisconnectIdleDbConnections addition stays under RequestTerminated.
 */
class OctaneListenersConfigTest extends TestCase
{
    private array $listeners;

    protected function setUp(): void
    {
        parent::setUp();

        // config/octane.php calls env(); it is defined by the Composer autoloader
        // (vendor/autoload.php is the PHPUnit bootstrap), so the bare require below
        // resolves without booting the full framework.
        $config = require __DIR__ . '/../../config/octane.php';
        $this->listeners = $config['listeners'];
    }

    public function test_flush_log_context_is_present_under_request_received(): void
    {
        $requestReceived = $this->listeners[RequestReceived::class] ?? [];

        $this->assertContains(
            FlushLogContext::class,
            $requestReceived,
            'FlushLogContext must run on RequestReceived or request_id leaks across requests on the same worker.'
        );
    }

    public function test_per_request_reset_listeners_are_present(): void
    {
        $requestReceived = $this->listeners[RequestReceived::class] ?? [];

        $this->assertContains(
            GiveNewRequestInstanceToApplication::class,
            $requestReceived,
            'The full default per-request listener list must be preserved (not a partial override).'
        );

        // The default RequestReceived list is the union of the two prepare arrays.
        $expectedCount = count(Octane::prepareApplicationForNextOperation())
            + count(Octane::prepareApplicationForNextRequest());

        $this->assertGreaterThanOrEqual(
            $expectedCount,
            count($requestReceived),
            'RequestReceived must contain the complete default listener set.'
        );
    }

    public function test_disconnect_idle_db_connections_remains_under_request_terminated(): void
    {
        $requestTerminated = $this->listeners[RequestTerminated::class] ?? [];

        $this->assertContains(
            DisconnectIdleDbConnections::class,
            $requestTerminated,
            'The app DB-connection cleanup listener must stay registered under RequestTerminated.'
        );
    }
}
