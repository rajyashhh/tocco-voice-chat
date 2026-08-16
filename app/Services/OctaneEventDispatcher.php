<?php

namespace App\Services;

use App\Services\OctaneBroadcasterService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Events\Dispatcher;

/**
 * Custom Event Dispatcher for Octane
 * Automatically rebuilds broadcaster before any broadcast events
 * 
 * Replaces the default dispatcher in the service container
 */
class OctaneEventDispatcher extends Dispatcher
{
    /**
     * Fire an event and call the listeners.
     *
     * @param  string|object  $event
     * @param  mixed  $payload
     * @param  bool  $halt
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        // If event implements ShouldBroadcast, refresh broadcaster first
        if (is_object($event) && $event instanceof ShouldBroadcast) {
            if (OctaneBroadcasterService::isOctane()) {
                OctaneBroadcasterService::rebuildBroadcaster();
            }
        }

        // Dispatch normally
        return parent::dispatch($event, $payload, $halt);
    }
}
