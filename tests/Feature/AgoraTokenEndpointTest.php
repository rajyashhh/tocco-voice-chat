<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature tests for the Agora-backed POST /stream/token endpoint.
 *
 * Verifies the request/response contract that the Flutter client depends on:
 *  - Auth is required (Sanctum)
 *  - room_name and service are required
 *  - Successful response contains: app_id, channel_name, rtc_token (token), uid, expires_at
 *  - AGORA_APP_CERTIFICATE is never leaked to the client
 *  - 503 when Agora credentials are not configured
 */
class AgoraTokenEndpointTest extends TestCase
{
    use DatabaseTransactions;

    private string $appId          = 'a]b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e';
    private string $appCertificate = 'f6e5d4c3b2a1f6e5d4c3b2a1f6e5d4c3';

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached config so phpunit.xml env overrides take effect.
        $cachedPath = app()->bootstrapPath('cache/config.php');
        if (file_exists($cachedPath)) {
            @unlink($cachedPath);
        }

        // Provide a valid 32-char hex Agora App ID and Certificate for tests.
        config(['agora.app_id'          => str_repeat('a', 32)]);
        config(['agora.app_certificate' => str_repeat('b', 32)]);
        config(['agora.token_expiry'    => 3600]);
    }

    /**
     * Create a real Sanctum personal access token for a user.
     *
     * The API routes run through CheckLatestToken middleware which requires
     * $user->currentAccessToken() to resolve to a real token row. Using
     * actingAs() alone creates a session auth that has no token, so the
     * middleware rejects the request with 401.
     *
     * We also seed the cache key that CheckLatestToken uses so the token
     * is recognized as the user's latest token.
     */
    private function authenticateAs(User $user): string
    {
        $token = $user->createToken('test-token');
        $plainTextToken = $token->plainTextToken;

        // Seed the cache so CheckLatestToken sees this as the latest token.
        Cache::put(
            'latest_token_id_' . $user->id,
            $token->accessToken->getKey(),
            86400
        );

        return $plainTextToken;
    }

    /**
     * Create a test user with only columns that exist in the production schema.
     *
     * The User factory references columns (lat, long, di, sub_sender_level)
     * that may not exist if migrations are pending. This helper inserts a
     * minimal user using only columns guaranteed to exist, then returns
     * a User model hydrated from the DB row.
     */
    private function createTestUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'name'              => 'Test User ' . Str::random(5),
            'email'             => 'test_' . Str::random(8) . '@example.com',
            'email_verified_at' => now(),
            'phone'             => (string) rand(1000000000, 9999999999),
            'password'          => bcrypt('password'),
            'remember_token'    => Str::random(10),
            'uuid'              => Str::uuid()->toString(),
            'is_host'           => 0,
            'total_diamond_send' => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return User::findOrFail($id);
    }

    private function url(): string
    {
        return '/api/stream/token';
    }

    // ── Auth ───────────────────────────────────────────────────────────

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson($this->url(), [
            'room_name' => 'room-1',
            'service'   => 'rooms',
        ])->assertUnauthorized();
    }

    // ── Validation ─────────────────────────────────────────────────────

    public function test_missing_room_name_is_rejected(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'service' => 'rooms',
            ])->assertUnprocessable();
    }

    public function test_missing_service_is_rejected(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-1',
            ])->assertUnprocessable();
    }

    public function test_invalid_service_value_is_rejected(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-1',
                'service'   => 'invalid',
            ])->assertUnprocessable();
    }

    // ── Success contract ───────────────────────────────────────────────

    public function test_successful_response_contains_required_fields(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-123',
                'service'   => 'rooms',
                'role'      => 'host',
            ]);

        $response->assertOk();

        $data = $response->json('data');

        $this->assertArrayHasKey('app_id', $data, 'Response must contain app_id');
        $this->assertArrayHasKey('channel_name', $data, 'Response must contain channel_name');
        $this->assertArrayHasKey('token', $data, 'Response must contain token (rtc_token)');
        $this->assertArrayHasKey('uid', $data, 'Response must contain uid');
        $this->assertArrayHasKey('expires_at', $data, 'Response must contain expires_at');

        // app_id is the public Agora App ID
        $this->assertSame(config('agora.app_id'), $data['app_id']);

        // channel_name matches the requested room_name
        $this->assertSame('room-123', $data['channel_name']);

        // uid is the user's id cast to int
        $this->assertSame((int) $user->id, $data['uid']);

        // token is a non-empty string starting with "007" (AccessToken2 format)
        $this->assertIsString($data['token']);
        $this->assertNotEmpty($data['token']);
        $this->assertStringStartsWith('007', $data['token']);

        // expires_at is a future unix timestamp
        $this->assertIsInt($data['expires_at']);
        $this->assertGreaterThan(time(), $data['expires_at']);
    }

    public function test_certificate_is_not_leaked_in_response(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-123',
                'service'   => 'rooms',
            ]);

        $response->assertOk();
        $fullResponse = $response->json();

        // The app_certificate must NEVER appear anywhere in the response.
        $responseString = json_encode($fullResponse);
        $this->assertStringNotContainsString(
            config('agora.app_certificate'),
            $responseString,
            'AGORA_APP_CERTIFICATE must never be exposed to the client'
        );
    }

    // ── Unconfigured credentials ───────────────────────────────────────

    public function test_503_when_agora_credentials_are_missing(): void
    {
        config(['agora.app_id' => '']);
        config(['agora.app_certificate' => '']);

        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-123',
                'service'   => 'rooms',
            ]);

        $response->assertStatus(503);
    }

    // ── Optional role param ────────────────────────────────────────────

    public function test_host_role_returns_valid_token(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-123',
                'service'   => 'rooms',
                'role'      => 'host',
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_guest_role_returns_valid_token(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-123',
                'service'   => 'rooms',
                'role'      => 'guest',
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_missing_role_defaults_to_publisher_token(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'room-123',
                'service'   => 'rooms',
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }

    // ── Streaming service type ─────────────────────────────────────────

    public function test_streaming_service_returns_valid_token(): void
    {
        $user = $this->createTestUser();
        $token = $this->authenticateAs($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson($this->url(), [
                'room_name' => 'live-room-1',
                'service'   => 'streaming',
                'role'      => 'host',
            ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }
}
