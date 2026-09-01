<?php

namespace Tests\Unit\Services;

use App\Services\AgoraTokenService;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic unit tests for AgoraTokenService (Agora RTC AccessToken2).
 *
 * No Laravel container — the service accepts explicit constructor params.
 * Covers:
 *  - Token generation with valid credentials
 *  - AccessToken2 (007) format validation
 *  - UID mapping (uint32 range)
 *  - Role → publisher/subscriber mapping
 *  - Config guard (unconfigured → null)
 *  - Token contains expected structure (base64-decodable, zlib-decodable)
 */
class AgoraTokenServiceTest extends TestCase
{
    private string $appId;
    private string $appCert;

    protected function setUp(): void
    {
        parent::setUp();
        $this->appId   = str_repeat('a', 32);
        $this->appCert = str_repeat('b', 32);
    }

    private function service(): AgoraTokenService
    {
        return new AgoraTokenService($this->appId, $this->appCert, 3600);
    }

    // ── Config guard ───────────────────────────────────────────────────

    public function test_is_configured_returns_true_when_credentials_set(): void
    {
        $this->assertTrue($this->service()->isConfigured());
    }

    public function test_is_configured_returns_false_when_app_id_missing(): void
    {
        $svc = new AgoraTokenService('', $this->appCert, 3600);
        $this->assertFalse($svc->isConfigured());
    }

    public function test_is_configured_returns_false_when_app_certificate_missing(): void
    {
        $svc = new AgoraTokenService($this->appId, '', 3600);
        $this->assertFalse($svc->isConfigured());
    }

    public function test_generate_returns_null_when_not_configured(): void
    {
        $svc = new AgoraTokenService('', '', 3600);
        $this->assertNull($svc->generateRtcToken(123, 'test-channel'));
    }

    // ── Token generation ───────────────────────────────────────────────

    public function test_generate_returns_array_with_expected_keys(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99', 'host');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('uid', $result);
        $this->assertArrayHasKey('expires_at', $result);
    }

    public function test_token_is_nonempty_string(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $this->assertIsString($result['token']);
        $this->assertNotEmpty($result['token']);
    }

    public function test_token_starts_with_007_version_prefix(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $this->assertStringStartsWith('007', $result['token']);
    }

    public function test_token_body_is_valid_base64(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $body   = substr($result['token'], 3);
        $decoded = base64_decode($body, true);
        $this->assertNotEmpty($decoded);
        $this->assertNotFalse($decoded);
    }

    public function test_token_body_decompresses_with_zlib(): void
    {
        $result  = $this->service()->generateRtcToken(42, 'room-99');
        $body    = substr($result['token'], 3);
        $decoded = base64_decode($body, true);
        $decompressed = zlib_decode($decoded);
        $this->assertNotEmpty($decompressed);
        $this->assertNotFalse($decompressed);
    }

    public function test_uid_is_int(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $this->assertIsInt($result['uid']);
    }

    public function test_expires_at_is_int(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $this->assertIsInt($result['expires_at']);
    }

    // ── UID mapping ────────────────────────────────────────────────────

    public function test_uid_matches_user_id_for_valid_range(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $this->assertSame(42, $result['uid']);
    }

    public function test_uid_mapping_rejects_zero(): void
    {
        $this->assertNull($this->service()->generateRtcToken(0, 'room-99'));
    }

    public function test_uid_mapping_rejects_negative(): void
    {
        $this->assertNull($this->service()->generateRtcToken(-5, 'room-99'));
    }

    public function test_uid_mapping_rejects_over_uint32(): void
    {
        // 2^32 = 4294967296 → exceeds uint32 max (4294967295)
        $this->assertNull($this->service()->generateRtcToken(4_294_967_296, 'room-99'));
    }

    public function test_uid_mapping_accepts_max_uint32(): void
    {
        $result = $this->service()->generateRtcToken(4_294_967_295, 'room-99');
        $this->assertNotNull($result);
        $this->assertSame(4_294_967_295, $result['uid']);
    }

    public function test_uid_mapping_accepts_large_typical_id(): void
    {
        $result = $this->service()->generateRtcToken(1_000_000, 'room-99');
        $this->assertNotNull($result);
        $this->assertSame(1_000_000, $result['uid']);
    }

    public function test_mapUserIdToUid_is_deterministic(): void
    {
        $svc = $this->service();
        $this->assertSame($svc->mapUserIdToUid(42), $svc->mapUserIdToUid(42));
    }

    public function test_mapUserIdToUid_returns_null_for_zero(): void
    {
        $this->assertNull($this->service()->mapUserIdToUid(0));
    }

    public function test_mapUserIdToUid_returns_null_for_negative(): void
    {
        $this->assertNull($this->service()->mapUserIdToUid(-1));
    }

    public function test_mapUserIdToUid_returns_null_for_overflow(): void
    {
        $this->assertNull($this->service()->mapUserIdToUid(4_294_967_296));
    }

    public function test_mapUserIdToUid_returns_int_for_valid(): void
    {
        $this->assertSame(999, $this->service()->mapUserIdToUid(999));
    }

    // ── Role mapping ───────────────────────────────────────────────────

    public function test_host_role_produces_valid_token(): void
    {
        $result = $this->service()->generateRtcToken(1, 'room-1', 'host');
        $this->assertNotNull($result);
        $this->assertNotEmpty($result['token']);
    }

    public function test_admin_role_produces_valid_token(): void
    {
        $result = $this->service()->generateRtcToken(1, 'room-1', 'admin');
        $this->assertNotNull($result);
        $this->assertNotEmpty($result['token']);
    }

    public function test_guest_role_produces_valid_token(): void
    {
        $result = $this->service()->generateRtcToken(1, 'room-1', 'guest');
        $this->assertNotNull($result);
        $this->assertNotEmpty($result['token']);
    }

    public function test_audience_role_produces_valid_token(): void
    {
        $result = $this->service()->generateRtcToken(1, 'room-1', 'audience');
        $this->assertNotNull($result);
    }

    public function test_visitor_role_produces_valid_token(): void
    {
        $result = $this->service()->generateRtcToken(1, 'room-1', 'visitor');
        $this->assertNotNull($result);
    }

    public function test_null_role_defaults_to_publisher(): void
    {
        $result = $this->service()->generateRtcToken(1, 'room-1', null);
        $this->assertNotNull($result);
    }

    // ── Expiry / expires_at ────────────────────────────────────────────

    public function test_expires_at_is_in_the_future(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99');
        $this->assertGreaterThan(time(), $result['expires_at']);
    }

    public function test_expires_at_respects_custom_expiry(): void
    {
        $result = $this->service()->generateRtcToken(42, 'room-99', null, 600);
        $this->assertGreaterThanOrEqual(time() + 590, $result['expires_at']);
        $this->assertLessThanOrEqual(time() + 610, $result['expires_at']);
    }

    public function test_expires_at_uses_constructor_expiry(): void
    {
        $svc    = new AgoraTokenService($this->appId, $this->appCert, 1800);
        $result = $svc->generateRtcToken(42, 'room-99');
        $this->assertGreaterThanOrEqual(time() + 1790, $result['expires_at']);
        $this->assertLessThanOrEqual(time() + 1810, $result['expires_at']);
    }

    // ── Determinism / uniqueness ───────────────────────────────────────

    public function test_tokens_are_unique_per_call(): void
    {
        $svc = $this->service();
        $t1  = $svc->generateRtcToken(42, 'room-99');
        $t2  = $svc->generateRtcToken(42, 'room-99');
        $this->assertNotSame($t1['token'], $t2['token']);
    }

    // ── Config file validation ─────────────────────────────────────────

    public function test_config_file_has_expected_keys(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/agora.php';

        $this->assertIsArray($config);
        $this->assertArrayHasKey('app_id', $config);
        $this->assertArrayHasKey('app_certificate', $config);
        $this->assertArrayHasKey('token_expiry', $config);
        $this->assertSame(3600, $config['token_expiry']);
    }
}
