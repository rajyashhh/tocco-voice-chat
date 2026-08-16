<?php

namespace App\Listeners;

use Laravel\Sanctum\Events\TokenAuthenticated;
use App\Services\SanctumTokenService;

/**
 * Listener to safely update token last_used_at timestamp
 * 
 * This listener handles the TokenAuthenticated event and updates the
 * last_used_at timestamp using the SanctumTokenService which includes
 * deadlock prevention and retry logic.
 */
class UpdateTokenLastUsedAt
{
    /**
     * Handle the event.
     */
    public function handle(TokenAuthenticated $event): void
    {
        // Use the service to update last_used_at with deadlock handling
        // The TokenAuthenticated event has a $token property, not $accessToken
        SanctumTokenService::updateLastUsedAt($event->token);
    }
}
