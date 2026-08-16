<?php

namespace Modules\Chat\Tests\Phase3;

use Tests\TestCase;

/**
 * Config unification gate (plan §4.2 / §4.5).
 *
 * The earlier review flagged a mismatch: the node config.json referenced TWO
 * HMAC secrets (${CENTRIFUGO_TOKEN_HMAC_SECRET} + ${CENTRIFUGO_SUBSCRIPTION_HMAC_SECRET})
 * and a split proxy-header story, while config/centrifugo.php + .env.example
 * expose only ONE. Laravel signs both the connection JWT and the 1:1 subscription
 * token with the SAME secret, so the node MUST verify with one shared secret and
 * the proxy header name MUST be identical on both sides, or auth/proxy break.
 *
 * This test pins the unified surface so it cannot silently drift apart again. It
 * is filesystem/config only — no DB, no app HTTP — so it is cheap and stable.
 */
class CentrifugoConfigUnificationTest extends TestCase
{
    private function configJsonPath(): string
    {
        return base_path('realtime/centrifugo/config.json');
    }

    private function rawConfigJson(): string
    {
        $path = $this->configJsonPath();
        $this->assertFileExists($path, 'Centrifugo node config.json must exist');

        return file_get_contents($path);
    }

    private function decodedConfigJson(): array
    {
        // The file uses "//" comment keys (Centrifugo allows them) but is still
        // valid JSON, so a plain json_decode must succeed.
        $decoded = json_decode($this->rawConfigJson(), true);
        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            'realtime/centrifugo/config.json must be valid JSON: ' . json_last_error_msg()
        );

        return $decoded;
    }

    // ---------------------------------------------------------------------
    // Laravel side: config/centrifugo.php
    // ---------------------------------------------------------------------

    public function test_laravel_config_exposes_single_hmac_and_unified_proxy_header(): void
    {
        // Defaults must hold even with no env set.
        $this->assertSame(
            'X-Centrifugo-Proxy-Secret',
            config('centrifugo.proxy_secret_header'),
            'proxy_secret_header must default to the unified X-Centrifugo-Proxy-Secret'
        );

        // Exactly one HMAC key in the Laravel config (no token/subscription split).
        $config = require config_path('centrifugo.php');
        $this->assertArrayHasKey('hmac_secret', $config, 'config/centrifugo.php must expose a single hmac_secret');
        $this->assertArrayNotHasKey('token_hmac_secret', $config);
        $this->assertArrayNotHasKey('subscription_hmac_secret', $config);

        // The single HMAC binds to the single env var.
        $raw = file_get_contents(config_path('centrifugo.php'));
        $this->assertStringContainsString("env('CENTRIFUGO_HMAC_SECRET')", $raw);
        $this->assertStringNotContainsString('CENTRIFUGO_TOKEN_HMAC_SECRET', $raw);
        $this->assertStringNotContainsString('CENTRIFUGO_SUBSCRIPTION_HMAC_SECRET', $raw);
    }

    // ---------------------------------------------------------------------
    // Node side: realtime/centrifugo/config.json
    // ---------------------------------------------------------------------

    public function test_node_config_uses_one_unified_hmac_placeholder(): void
    {
        $raw = $this->rawConfigJson();

        // The node verifies tokens with the SAME single secret Laravel signs with.
        $this->assertStringContainsString('${CENTRIFUGO_HMAC_SECRET}', $raw,
            'node config.json must reference the unified ${CENTRIFUGO_HMAC_SECRET}');

        // The previously-mismatched split secrets must be gone.
        $this->assertStringNotContainsString('${CENTRIFUGO_TOKEN_HMAC_SECRET}', $raw,
            'split ${CENTRIFUGO_TOKEN_HMAC_SECRET} must not exist anymore');
        $this->assertStringNotContainsString('${CENTRIFUGO_SUBSCRIPTION_HMAC_SECRET}', $raw,
            'split ${CENTRIFUGO_SUBSCRIPTION_HMAC_SECRET} must not exist anymore');

        // Structurally: client.token.hmac_secret_key carries the unified placeholder.
        $json = $this->decodedConfigJson();
        $this->assertSame(
            '${CENTRIFUGO_HMAC_SECRET}',
            $json['client']['token']['hmac_secret_key'] ?? null,
            'client.token.hmac_secret_key must be the unified HMAC placeholder'
        );

        // subscription_token MUST NOT define its own hmac block (it inherits the
        // single token secret — Centrifugo v6 default), keeping ONE secret.
        $this->assertArrayNotHasKey(
            'subscription_token',
            $json['client'] ?? [],
            'subscription_token must not define a separate secret block'
        );
    }

    public function test_node_config_proxy_header_matches_laravel_unified_header(): void
    {
        $json = $this->decodedConfigJson();

        $headers = $json['http_proxy']['static_http_headers'] ?? [];
        $this->assertArrayHasKey(
            'X-Centrifugo-Proxy-Secret',
            $headers,
            'node config.json must inject the unified X-Centrifugo-Proxy-Secret header'
        );
        $this->assertSame(
            '${CENTRIFUGO_PROXY_SECRET}',
            $headers['X-Centrifugo-Proxy-Secret'],
            'proxy header value must be the unified ${CENTRIFUGO_PROXY_SECRET} placeholder'
        );

        // Both sides must agree on the SAME header name.
        $this->assertSame(
            config('centrifugo.proxy_secret_header'),
            'X-Centrifugo-Proxy-Secret',
            'Laravel proxy_secret_header and the node static header name must be identical'
        );
    }

    // ---------------------------------------------------------------------
    // .env.example: documents exactly one secret per concern
    // ---------------------------------------------------------------------

    public function test_env_example_documents_single_unified_secrets(): void
    {
        $path = base_path('.env.example');
        $this->assertFileExists($path);
        $raw = file_get_contents($path);

        $this->assertStringContainsString('CENTRIFUGO_HMAC_SECRET=', $raw);
        $this->assertStringContainsString('CENTRIFUGO_PROXY_SECRET=', $raw);
        $this->assertStringContainsString('CENTRIFUGO_PROXY_SECRET_HEADER=X-Centrifugo-Proxy-Secret', $raw);

        // No split secrets leak into the documented surface.
        $this->assertStringNotContainsString('CENTRIFUGO_TOKEN_HMAC_SECRET', $raw);
        $this->assertStringNotContainsString('CENTRIFUGO_SUBSCRIPTION_HMAC_SECRET', $raw);
    }
}
