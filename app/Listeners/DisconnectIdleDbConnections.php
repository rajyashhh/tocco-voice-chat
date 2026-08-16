<?php

namespace App\Listeners;

use Laravel\Octane\Events\RequestTerminated;

class DisconnectIdleDbConnections
{
    /**
     * Disconnect all idle database connections after each Octane request.
     * This prevents connection leak where workers hold sleeping connections for hours.
     */
    public function handle(RequestTerminated $event): void
    {
        if (app()->bound('db')) {
            foreach (app('db')->getConnections() as $connection) {
                $connection->disconnect();
            }
        }
    }
}
