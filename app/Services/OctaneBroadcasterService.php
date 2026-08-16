<?php

namespace App\Services;

/**
 * Octane broadcaster helper.
 *
 * The per-request Pusher credential rebuild this service used to perform is no
 * longer needed: Centrifugo is the only broadcaster and it reads its static
 * config/secret once, so there is nothing to refresh between requests. The
 * mutating methods are kept as no-ops so existing callers
 * (OctaneEventDispatcher, BroadcastsWithFreshCredentials, ConfigController)
 * keep working without change. Only `isOctane()` retains real behaviour.
 */
class OctaneBroadcasterService
{
    /** No-op: Centrifugo needs no per-request broadcaster rebuild. */
    public static function rebuildBroadcaster(): void
    {
        //
    }

    /** No-op: kept for the TickReceived call sites. */
    public static function rebuildBroadcasterPeriodically(int $tickInterval = 10): void
    {
        //
    }

    /** No-op: Centrifugo config is static, no runtime DB sync required. */
    public static function updateRuntimeConfigFromDb(): void
    {
        //
    }

    /**
     * Check if running in Octane environment.
     */
    public static function isOctane(): bool
    {
        try {
            if (method_exists(app(), 'runningInOctane')) {
                return app()->runningInOctane();
            }

            return class_exists('Laravel\Octane\Octane');
        } catch (\Exception $e) {
            return false;
        }
    }
}
