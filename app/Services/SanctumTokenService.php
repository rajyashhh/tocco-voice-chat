<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Service to handle Sanctum token updates with deadlock prevention
 * 
 * This service provides a safe way to update the last_used_at timestamp
 * on personal access tokens without causing database deadlocks in high-concurrency
 * environments like Laravel Octane.
 */
class SanctumTokenService
{
    /**
     * Maximum number of retry attempts for deadlock recovery
     */
    private const MAX_RETRIES = 3;

    /**
     * Base delay in milliseconds between retries
     */
    private const RETRY_DELAY_MS = 100;

    /**
     * Update the last_used_at timestamp for a token with deadlock handling
     *
     * @param PersonalAccessToken $token
     * @return bool
     */
    public static function updateLastUsedAt(PersonalAccessToken $token): bool
    {
        $retries = 0;

        while ($retries < self::MAX_RETRIES) {
            try {
                // Use a direct update query with a short timeout to avoid long locks
                DB::table('personal_access_tokens')
                    ->where('id', $token->id)
                    ->update([
                        'last_used_at' => now(),
                        'updated_at' => now(),
                    ]);

                return true;
            } catch (\Exception $e) {
                // Check if this is a deadlock error (MySQL error 1213)
                if (str_contains($e->getMessage(), '1213') || str_contains($e->getMessage(), 'Deadlock')) {
                    $retries++;

                    if ($retries >= self::MAX_RETRIES) {
                        // Log the error but don't throw - token authentication should still succeed
                        \Log::warning('Failed to update token last_used_at after retries', [
                            'token_id' => $token->id,
                            'error' => $e->getMessage(),
                        ]);

                        return false;
                    }

                    // Exponential backoff: 100ms, 200ms, 400ms
                    $delay = self::RETRY_DELAY_MS * (2 ** ($retries - 1));
                    usleep($delay * 1000);

                    continue;
                }

                // For non-deadlock errors, log and fail
                \Log::error('Error updating token last_used_at', [
                    'token_id' => $token->id,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        return false;
    }

    /**
     * Batch update last_used_at for multiple tokens (useful for cleanup operations)
     *
     * @param array $tokenIds
     * @return int Number of successfully updated tokens
     */
    public static function batchUpdateLastUsedAt(array $tokenIds): int
    {
        if (empty($tokenIds)) {
            return 0;
        }

        try {
            return DB::table('personal_access_tokens')
                ->whereIn('id', $tokenIds)
                ->update([
                    'last_used_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (\Exception $e) {
            \Log::error('Error in batch update of token last_used_at', [
                'token_count' => count($tokenIds),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
