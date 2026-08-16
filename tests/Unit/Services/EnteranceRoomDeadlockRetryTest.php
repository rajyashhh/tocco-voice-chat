<?php

namespace Tests\Unit\Services;

use App\Tik\Services\EnteranceRoomServices;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Proves the deadlock-retry safety net on the critical room visitor write path
 * (EnteranceRoomServices::withDeadlockRetry), mirroring the SanctumTokenService
 * contract: MAX_RETRIES=3 with exponential backoff, retry ONLY on MySQL 1213 /
 * "Deadlock", and NEVER throw out of the webhook (so it always returns 200).
 *
 * The retry guard is the last line of defense behind the atomic insertOrIgnore
 * + UNIQUE(room_id, user_id) constraint that already eliminates the structural
 * deadlock; this test pins its behavior.
 */
class EnteranceRoomDeadlockRetryTest extends TestCase
{
    private function service(): EnteranceRoomServices
    {
        // Bypass the (RoomRepository, UserRepository) constructor — the retry
        // helper does not touch instance state.
        return (new ReflectionClass(EnteranceRoomServices::class))
            ->newInstanceWithoutConstructor();
    }

    private function retryMethod(): ReflectionMethod
    {
        $method = new ReflectionMethod(EnteranceRoomServices::class, 'withDeadlockRetry');
        $method->setAccessible(true);

        return $method;
    }

    public function test_succeeds_on_first_attempt_without_retry(): void
    {
        $calls = 0;
        $op = function () use (&$calls) {
            $calls++;
        };

        $this->retryMethod()->invoke($this->service(), $op);

        $this->assertSame(1, $calls, 'A successful write must run exactly once.');
    }

    public function test_retries_on_deadlock_then_succeeds(): void
    {
        $calls = 0;
        $op = function () use (&$calls) {
            $calls++;
            if ($calls < 3) {
                throw new \RuntimeException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found');
            }
        };

        $this->retryMethod()->invoke($this->service(), $op);

        $this->assertSame(3, $calls, 'It must retry the deadlocked write until it succeeds (within MAX_RETRIES).');
    }

    public function test_gives_up_after_max_retries_without_throwing(): void
    {
        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('error')->never();

        $calls = 0;
        $op = function () use (&$calls) {
            $calls++;
            throw new \RuntimeException('1213 Deadlock found when trying to get lock');
        };

        // Must NOT throw — the webhook still returns 200 even on persistent deadlock.
        $this->retryMethod()->invoke($this->service(), $op);

        $this->assertSame(3, $calls, 'It must attempt exactly MAX_RETRIES (3) times before giving up.');
    }

    public function test_non_deadlock_error_is_logged_and_not_retried(): void
    {
        Log::shouldReceive('error')->once();
        Log::shouldReceive('warning')->never();

        $calls = 0;
        $op = function () use (&$calls) {
            $calls++;
            throw new \RuntimeException('Some other DB error');
        };

        $this->retryMethod()->invoke($this->service(), $op);

        $this->assertSame(1, $calls, 'A non-deadlock error must fail fast (single attempt), not retry.');
    }
}
