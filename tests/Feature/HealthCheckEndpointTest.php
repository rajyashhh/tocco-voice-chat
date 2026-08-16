<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckEndpointTest extends TestCase
{
    /**
     * Test that the health endpoint returns 200 OK.
     */
    public function test_health_endpoint_returns_200_ok(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
        $response->assertSee('ok');
    }

    /**
     * Test that the health endpoint response body is exactly 'ok'.
     */
    public function test_health_endpoint_response_body_is_ok(): void
    {
        $response = $this->get('/api/health');

        $this->assertEquals('ok', $response->getContent());
    }

    /**
     * Test that the health endpoint does not require authentication.
     */
    public function test_health_endpoint_does_not_require_authentication(): void
    {
        // Call without any auth headers
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        // Should NOT return 401 Unauthorized
        $this->assertNotEquals(401, $response->getStatusCode());
    }

    /**
     * Test that the health endpoint is not affected by rate limiting.
     * Sends 100 rapid requests — all should return 200, none should return 429.
     */
    public function test_health_endpoint_is_not_rate_limited(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $response = $this->get('/api/health');
            $response->assertStatus(200);
        }
    }

    /**
     * Test that the health endpoint only responds to GET method.
     */
    public function test_health_endpoint_only_accepts_get_method(): void
    {
        $this->get('/api/health')->assertStatus(200);
        $this->post('/api/health')->assertStatus(405);
        $this->put('/api/health')->assertStatus(405);
        $this->delete('/api/health')->assertStatus(405);
    }
}
