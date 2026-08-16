<?php

namespace Modules\Chat\Tests\Phase6;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Chat\Http\Controllers\GroupController;

/**
 * Harness for the Phase 6 group HTTP SURFACE tests (gate item 2).
 *
 * Unlike the service-level Phase 6 cases (which call GroupService directly), this
 * drives the REAL GroupController actions resolved from the container, with a REAL
 * authenticated App\Models\User bound on the request — exactly what the route binds
 * after auth:sanctum (Modules/Chat/Routes/api.php). It proves, per endpoint:
 *
 *   - the back end is the guard: a non-staff caller is rejected (403) and the
 *     forbidden action does not mutate state;
 *   - the success/error envelope is the project contract {success, message, data}
 *     (Common::apiResponse);
 *   - a GroupException is translated to its stable HTTP status (403/404/409/422);
 *   - route-layer failures (unknown id -> findOrFail 404; bad input -> validation
 *     422) surface as the framework status the route would return.
 *
 * It reuses the GroupServiceTestCase schema/fixtures (real MariaDB, the Phase 1
 * group DDL) so the controller -> service -> policy -> system-event -> audit graph
 * runs end to end against the production wiring.
 */
abstract class GroupEndpointHttpTestCase extends GroupServiceTestCase
{
    /**
     * GroupMemberResource reads $user->profile?->avatar, so the members list
     * endpoint touches the profiles table. The base harness only lays down
     * users/chat_rooms/chat_messages; add an empty profiles table so the hasOne
     * lookup degrades to null (no avatar) instead of erroring on a missing table.
     */
    protected function buildPrerequisiteSchema(): void
    {
        parent::buildPrerequisiteSchema();

        \Illuminate\Support\Facades\Schema::create('profiles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('avatar')->nullable();
            $table->integer('gender')->nullable();
            $table->timestamps();
        });
    }

    protected function controller(): GroupController
    {
        return $this->app->make(GroupController::class);
    }

    /**
     * Lay down a profile row (with a real avatar) for a seeded user, so the member
     * resource actually dereferences $user->profile?->avatar against ground truth.
     * Used by the listMembers N+1 guard, where a missing/null profile could mask a
     * per-row lazy load.
     */
    protected function seedProfile(int $userId, ?string $avatar = null): void
    {
        \Illuminate\Support\Facades\DB::table('profiles')->insert([
            'user_id' => $userId,
            'avatar'  => $avatar ?? "avatars/u{$userId}.png",
            'gender'  => 1,
        ]);
    }

    /**
     * Build a real Request bound to the authenticated caller, exactly as the route
     * pipeline hands it to the action after auth:sanctum. The container `request`
     * binding is set so resources/helpers that call request() resolve to this one.
     */
    protected function request(User $actor, array $body = [], array $query = []): Request
    {
        $request = Request::create('/api/groups', 'POST', array_merge($body, $query));
        $request->setUserResolver(fn () => $actor);
        \Illuminate\Support\Facades\Auth::setUser($actor);
        $this->app->instance('request', $request);

        return $request;
    }

    /**
     * Decode a controller JsonResponse into [status, body-array].
     *
     * @return array{status:int, body:array<string,mixed>}
     */
    protected function decode(JsonResponse $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'body'   => json_decode($response->getContent(), true) ?? [],
        ];
    }

    /**
     * Assert the standard success envelope shape (success=true + the three keys the
     * project contract guarantees).
     *
     * @param  array{status:int, body:array<string,mixed>}  $decoded
     */
    protected function assertSuccessEnvelope(array $decoded, int $expectedStatus = 200): void
    {
        $this->assertSame($expectedStatus, $decoded['status'], 'unexpected HTTP status');
        $this->assertArrayHasKey('success', $decoded['body']);
        $this->assertArrayHasKey('message', $decoded['body']);
        $this->assertArrayHasKey('data', $decoded['body']);
        $this->assertTrue($decoded['body']['success'], 'success flag must be true');
    }

    /**
     * Assert the standard error envelope: success=false at the given status.
     *
     * @param  array{status:int, body:array<string,mixed>}  $decoded
     */
    protected function assertErrorEnvelope(array $decoded, int $expectedStatus): void
    {
        $this->assertSame($expectedStatus, $decoded['status'], 'unexpected HTTP error status');
        $this->assertArrayHasKey('success', $decoded['body']);
        $this->assertArrayHasKey('message', $decoded['body']);
        $this->assertArrayHasKey('data', $decoded['body']);
        $this->assertFalse($decoded['body']['success'], 'success flag must be false on error');
    }
}
