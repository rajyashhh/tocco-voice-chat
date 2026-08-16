<?php

namespace Tests\Unit\TaskStream;

use Tests\TestCase;
use Mockery;
use ReflectionMethod;
use App\Exceptions\CValidationException;
use Modules\TaskStream\Services\TaskStreamValidationService;
use Modules\TaskStream\Repositories\TaskStreamRepository;
use Modules\TaskStream\Repositories\TaskStreamRoomRepository;

/**
 * Guard for "Call to a member function ownerRoom() on null" (643/24h).
 * When no user is resolved, the service must throw a handled CValidationException
 * (422) instead of a fatal Error on null->ownerRoom().
 */
class ValidateAuthLiveRoomNullGuardTest extends TestCase
{
    public function test_null_user_throws_validation_exception_not_fatal_error(): void
    {
        $service = new TaskStreamValidationService(
            Mockery::mock(TaskStreamRepository::class),
            Mockery::mock(TaskStreamRoomRepository::class),
        );

        $method = new ReflectionMethod($service, 'validateAuthLiveRoom');
        $method->setAccessible(true);

        $this->expectException(CValidationException::class);

        // checkUser = null and no authenticated user -> guard must fire.
        $method->invoke($service, null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
