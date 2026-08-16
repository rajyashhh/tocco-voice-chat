<?php

namespace Modules\Chat\Tests\Phase3;

use App\Http\Middleware\VerifyCentrifugoProxy;
use Illuminate\Http\Request;

/**
 * Phase 3 §4.2c — the shared-secret guard on the subscribe-proxy hop.
 *
 * The proxy endpoint is server-to-server (Centrifugo -> Laravel), so the only
 * thing standing between a forged client request and a membership grant is this
 * secret check. It must fail CLOSED (deny) on a missing/mismatched/absent secret.
 */
class VerifyCentrifugoProxyTest extends Phase3AuthTestCase
{
    private function pass(Request $request): array
    {
        $reached = false;
        $response = (new VerifyCentrifugoProxy())->handle($request, function () use (&$reached) {
            $reached = true;
            return response()->json(['result' => (object) []]);
        });

        return ['reached' => $reached, 'body' => json_decode($response->getContent(), true)];
    }

    private function proxyRequest(?string $secret): Request
    {
        $request = Request::create('/centrifugo/subscribe', 'POST', [], [], [], [], '{}');
        if ($secret !== null) {
            $request->headers->set('X-Centrifugo-Proxy-Secret', $secret);
        }

        return $request;
    }

    public function test_allows_request_with_correct_secret(): void
    {
        $out = $this->pass($this->proxyRequest($this->proxySecret));

        $this->assertTrue($out['reached'], 'correct secret must reach the controller');
        $this->assertArrayHasKey('result', $out['body']);
    }

    public function test_denies_request_with_wrong_secret(): void
    {
        $out = $this->pass($this->proxyRequest('totally-wrong'));

        $this->assertFalse($out['reached'], 'wrong secret must NOT reach the controller');
        $this->assertSame(403, $out['body']['error']['code']);
    }

    public function test_denies_request_with_missing_header(): void
    {
        $out = $this->pass($this->proxyRequest(null));

        $this->assertFalse($out['reached']);
        $this->assertSame(403, $out['body']['error']['code']);
    }

    public function test_fails_closed_when_secret_not_configured(): void
    {
        config(['centrifugo.proxy_secret' => '']);

        // Even with a header present, an unconfigured secret must deny.
        $out = $this->pass($this->proxyRequest('anything'));

        $this->assertFalse($out['reached'], 'unconfigured proxy secret must fail closed');
        $this->assertSame(403, $out['body']['error']['code']);
    }
}
