<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RequestId;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit Test: App\Http\Middleware\RequestId (correlation / X-Request-Id)
 *
 * يختبر الـ middleware معزولاً عبر استدعاء handle() مباشرة بطلب وهمي.
 * لا يلمس قاعدة البيانات ولا الـ HTTP kernel نهائياً، فهو آمن في أي بيئة.
 */
class RequestIdTest extends TestCase
{
    private function passThrough(Request $request): Response
    {
        return (new RequestId())->handle($request, function ($req) {
            return new Response('ok');
        });
    }

    public function test_generates_request_id_when_header_is_absent(): void
    {
        $request = Request::create('/health', 'GET');

        $response = $this->passThrough($request);

        $generated = $request->headers->get('X-Request-Id');

        // تم توليد معرّف وحقنه في الطلب
        $this->assertNotEmpty($generated, 'Request should receive a generated X-Request-Id.');

        // المعرّف المولّد هو UUID صالح
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $generated,
            'Generated X-Request-Id should be a valid UUID.'
        );

        // نفس المعرّف يظهر في ترويسة الاستجابة
        $this->assertSame(
            $generated,
            $response->headers->get('X-Request-Id'),
            'Response X-Request-Id must match the one set on the request.'
        );
    }

    public function test_preserves_incoming_request_id(): void
    {
        $incoming = 'client-supplied-correlation-123';

        $request = Request::create('/health', 'GET');
        $request->headers->set('X-Request-Id', $incoming);

        $response = $this->passThrough($request);

        // المعرّف الوارد محفوظ على الطلب
        $this->assertSame(
            $incoming,
            $request->headers->get('X-Request-Id'),
            'Incoming X-Request-Id must be preserved on the request.'
        );

        // والمعرّف نفسه يُعاد في الاستجابة
        $this->assertSame(
            $incoming,
            $response->headers->get('X-Request-Id'),
            'Incoming X-Request-Id must be propagated to the response.'
        );
    }

    public function test_blank_header_is_treated_as_absent_and_regenerated(): void
    {
        $request = Request::create('/health', 'GET');
        $request->headers->set('X-Request-Id', '');

        $response = $this->passThrough($request);

        // ترويسة فارغة falsy => يولّد معرّفاً جديداً (سلوك العامل ?:)
        $this->assertNotEmpty(
            $response->headers->get('X-Request-Id'),
            'An empty incoming X-Request-Id should be regenerated, not echoed back empty.'
        );
    }

    public function test_request_id_is_pushed_into_log_context(): void
    {
        $incoming = 'log-context-corr-id';

        Log::shouldReceive('withContext')
            ->once()
            ->with(['request_id' => $incoming]);

        $request = Request::create('/health', 'GET');
        $request->headers->set('X-Request-Id', $incoming);

        $this->passThrough($request);
    }
}
