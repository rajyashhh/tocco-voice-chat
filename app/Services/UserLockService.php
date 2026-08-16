<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Cache\LockTimeoutException;

class UserLockService
{
    private const LOCK_PREFIX = 'wallet_lock:user:';

    public function withWalletLock(
        int $userId,
        Closure $callback,
        int $timeoutSeconds = 10,
        int $waitSeconds = 5
    ) {
        $lockKey = self::LOCK_PREFIX . $userId;
        $lock = Cache::lock($lockKey, $timeoutSeconds);

        $startTime = microtime(true);

        try {
            Log::channel('wallet')->info('Attempting to acquire wallet lock', [
                'user_id' => $userId,
                'lock_key' => $lockKey,
                'timeout' => $timeoutSeconds,
                'wait' => $waitSeconds,
            ]);

            $lock->block($waitSeconds);

            $acquireTime = microtime(true) - $startTime;

            Log::channel('wallet')->info('Wallet lock acquired', [
                'user_id' => $userId,
                'lock_key' => $lockKey,
                'acquire_time_ms' => round($acquireTime * 1000, 2),
            ]);

            $callbackStartTime = microtime(true);

            try {
                $result = $callback();

                $callbackTime = microtime(true) - $callbackStartTime;

                Log::channel('wallet')->info('Wallet lock callback executed successfully', [
                    'user_id' => $userId,
                    'lock_key' => $lockKey,
                    'callback_time_ms' => round($callbackTime * 1000, 2),
                ]);

                return $result;

            } catch (\Exception $e) {
                $callbackTime = microtime(true) - $callbackStartTime;

                Log::channel('wallet')->error('Wallet lock callback failed', [
                    'user_id' => $userId,
                    'lock_key' => $lockKey,
                    'callback_time_ms' => round($callbackTime * 1000, 2),
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);

                throw $e;

            } finally {
                $lock->forceRelease();

                Log::channel('wallet')->info('Wallet lock released', [
                    'user_id' => $userId,
                    'lock_key' => $lockKey,
                    'total_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                ]);
            }

        } catch (LockTimeoutException $e) {
            Log::channel('wallet')->warning('Failed to acquire wallet lock - timeout', [
                'user_id' => $userId,
                'lock_key' => $lockKey,
                'timeout' => $timeoutSeconds,
                'wait' => $waitSeconds,
                'elapsed_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            throw new LockTimeoutException(
                "Could not acquire wallet lock for user {$userId} within {$waitSeconds} seconds"
            );
        }
    }

    public function withWalletLockRetry(
        int $userId,
        Closure $callback,
        int $timeoutSeconds = 10,
        int $waitSeconds = 5,
        int $maxRetries = 3
    ) {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return $this->withWalletLock($userId, $callback, $timeoutSeconds, $waitSeconds);

            } catch (LockTimeoutException $e) {
                $attempt++;

                if ($attempt >= $maxRetries) {
                    Log::channel('wallet')->critical('Failed to acquire wallet lock after max retries', [
                        'user_id' => $userId,
                        'max_retries' => $maxRetries,
                        'attempts' => $attempt,
                    ]);

                    throw $e;
                }

                $backoffMs = 100 * $attempt;

                Log::channel('wallet')->warning('Retrying wallet lock acquisition', [
                    'user_id' => $userId,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'backoff_ms' => $backoffMs,
                ]);

                usleep($backoffMs * 1000);
            }
        }
    }
}
