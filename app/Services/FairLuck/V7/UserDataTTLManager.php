<?php

namespace App\Services\FairLuck\V7;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * UserDataTTLManager: Manages Time-To-Live (TTL) for user RTP data
 * 
 * Purpose:
 * - Prevents old "debts" from accumulating indefinitely
 * - Protects against players returning after months to claim compensation
 * - Ensures fair RTP calculation based on recent activity
 * 
 * Default TTL: 90 days
 * After 90 days of inactivity, user's RTP data is reset
 */
class UserDataTTLManager
{
    private const KEY_PREFIX = 'fairluck:V7:user:';
    private const TTL_DAYS_DEFAULT = 90;
    private const LAST_ACTIVITY_KEY = 'last_activity_ts';

    /**
     * Get the TTL in days from settings
     */
    public static function getTTLDays(): int
    {
        return (int) \App\Models\FairLuckSetting::getByKey('fairluck_user_data_ttl_days', self::TTL_DAYS_DEFAULT);
    }

    /**
     * Get the TTL in seconds
     */
    public static function getTTLSeconds(): int
    {
        return self::getTTLDays() * 86400;
    }

    /**
     * Record user activity (update last activity timestamp)
     */
    public static function recordActivity(int $userId): void
    {
        $key = self::KEY_PREFIX . $userId;
        Redis::hset($key, self::LAST_ACTIVITY_KEY, time());
        
        // Set expiration on the entire hash
        Redis::expire($key, self::getTTLSeconds());
    }

    /**
     * Get last activity timestamp for a user
     */
    public static function getLastActivity(int $userId): ?int
    {
        $key = self::KEY_PREFIX . $userId;
        $timestamp = Redis::hget($key, self::LAST_ACTIVITY_KEY);
        
        return $timestamp ? (int) $timestamp : null;
    }

    /**
     * Check if user's data has expired
     */
    public static function isDataExpired(int $userId): bool
    {
        $lastActivity = self::getLastActivity($userId);
        
        if ($lastActivity === null) {
            return false; // No data exists
        }

        $ttlSeconds = self::getTTLSeconds();
        $ageSeconds = time() - $lastActivity;

        return $ageSeconds > $ttlSeconds;
    }

    /**
     * Get days remaining until expiration
     */
    public static function getDaysUntilExpiration(int $userId): int
    {
        $lastActivity = self::getLastActivity($userId);
        
        if ($lastActivity === null) {
            return self::getTTLDays();
        }

        $ttlSeconds = self::getTTLSeconds();
        $ageSeconds = time() - $lastActivity;
        $remainingSeconds = max(0, $ttlSeconds - $ageSeconds);

        return (int) ceil($remainingSeconds / 86400);
    }

    /**
     * Reset user's RTP data (clear old debt)
     */
    public static function resetUserData(int $userId): void
    {
        $key = self::KEY_PREFIX . $userId;
        
        // Log the reset

        // Delete the entire hash
        Redis::del($key);
    }

    /**
     * Check and reset expired user data
     * Should be called periodically (e.g., daily cron job)
     */
    public static function cleanupExpiredData(): array
    {
        $stats = [
            'checked' => 0,
            'expired' => 0,
            'reset' => 0,
        ];

        // Get all user keys from Redis
        $pattern = self::KEY_PREFIX . '*';
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            $stats['checked']++;
            
            // Extract user ID from key
            $userId = (int) str_replace(self::KEY_PREFIX, '', $key);
            
            if (self::isDataExpired($userId)) {
                $stats['expired']++;
                self::resetUserData($userId);
                $stats['reset']++;
            }
        }


        return $stats;
    }

    /**
     * Get user data status
     */
    public static function getUserDataStatus(int $userId): array
    {
        $lastActivity = self::getLastActivity($userId);
        $isExpired = self::isDataExpired($userId);
        $daysRemaining = self::getDaysUntilExpiration($userId);

        return [
            'user_id' => $userId,
            'last_activity' => $lastActivity ? date('Y-m-d H:i:s', $lastActivity) : null,
            'is_expired' => $isExpired,
            'days_remaining' => $daysRemaining,
            'ttl_days' => self::getTTLDays(),
            'status' => $isExpired ? 'expired' : 'active',
        ];
    }

    /**
     * Validate user data before using it
     * Returns true if data is still valid, false if expired
     */
    public static function validateUserData(int $userId): bool
    {
        if (self::isDataExpired($userId)) {
            self::resetUserData($userId);
            return false;
        }

        // Update last activity
        self::recordActivity($userId);
        return true;
    }
}
