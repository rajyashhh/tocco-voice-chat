<?php

namespace Tests\Unit\Services;

use App\Traits\HelperTraits\UtdStreamTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the UTD-STREAM client fixes:
 *
 *  (1) The request timeout must read config('utd_stream.timeout_seconds') — the old
 *      key 'utd_stream_timeout_seconds' existed in NO config file, so the timeout
 *      was hardwired to the 5s fallback and could never be tuned.
 *  (2) The circuit breaker must log when it opens (it used to drop every call to
 *      null in total silence for 60s) and when it closes again.
 *  (3) send-data gets a short transport-level retry/backoff instead of silently
 *      dropping room events on a single ConnectionException. The retry must never
 *      apply to HTTP error responses (when-filter on ConnectionException, throw=false).
 *
 * Pure logic (no container), in the project's source-reflection test style.
 */
class UtdStreamTraitShapeTest extends TestCase
{
    private function methodSource(string $method): string
    {
        $ref = new ReflectionMethod(UtdStreamTrait::class, $method);
        $file = file($ref->getFileName());
        $lines = array_slice(
            $file,
            $ref->getStartLine() - 1,
            $ref->getEndLine() - $ref->getStartLine() + 1
        );

        return implode('', $lines);
    }

    public function test_config_file_exists_with_expected_keys(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/utd_stream.php';

        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeout_seconds', $config);
        $this->assertArrayHasKey('send_data_timeout_seconds', $config);
        $this->assertArrayHasKey('send_data_retries', $config);
        $this->assertArrayHasKey('retry_backoff_ms', $config);

        // Defaults (no env overrides in the test run): timeout raised from the
        // hardwired 5s; send-data gets a SHORT timeout (6s) and a single extra
        // attempt so the worst-case worker hold is ~2 x 6s, not 3 x 10s.
        $this->assertSame(10, $config['timeout_seconds']);
        $this->assertSame(6, $config['send_data_timeout_seconds']);
        $this->assertSame(1, $config['send_data_retries']);
        $this->assertSame(150, $config['retry_backoff_ms']);
    }

    public function test_timeout_reads_the_real_config_key_not_the_dead_one(): void
    {
        $src = $this->methodSource('streamRequest');

        $this->assertStringContainsString("config('utd_stream.timeout_seconds'", $src);
        $this->assertStringNotContainsString(
            "config('utd_stream_timeout_seconds'",
            $src,
            "The flat key exists in no config file — reading it silently pins the timeout to its fallback."
        );
    }

    public function test_circuit_breaker_logs_on_open(): void
    {
        $src = $this->methodSource('recordStreamFailure');

        $this->assertStringContainsString('Log::warning', $src);
        $this->assertStringContainsString('circuit breaker OPENED', $src);
    }

    public function test_circuit_breaker_logs_on_close(): void
    {
        $src = $this->methodSource('streamRequest');

        $this->assertStringContainsString('circuit breaker closed', $src);
    }

    public function test_send_data_is_the_retrying_path(): void
    {
        $src = $this->methodSource('streamSendData');

        $this->assertStringContainsString(
            "config('utd_stream.send_data_retries'",
            $src,
            'send-data must pass a retry budget; one ConnectionException used to drop the event silently.'
        );
        $this->assertStringContainsString(
            "config('utd_stream.send_data_timeout_seconds'",
            $src,
            'send-data must use its own short timeout so retries do not hold an Octane worker for ~30s.'
        );
    }

    public function test_circuit_breaker_counts_every_failed_attempt(): void
    {
        $src = $this->methodSource('streamRequest');
        $retryBlock = substr($src, strpos($src, '->retry('), strpos($src, 'match (') - strpos($src, '->retry('));

        $this->assertStringContainsString(
            'recordStreamFailure',
            $retryBlock,
            'Each failed retry attempt must count toward the breaker, or a full outage opens it 2-3x slower.'
        );
    }

    public function test_retry_is_transport_only_and_does_not_throw_on_http_errors(): void
    {
        $src = $this->methodSource('streamRequest');

        $this->assertStringContainsString('instanceof ConnectionException', $src);
        $this->assertMatchesRegularExpression(
            '/->retry\(/',
            $src,
            'streamRequest must wire the retry budget into the HTTP client.'
        );
        $this->assertStringContainsString(
            'false',
            substr($src, strpos($src, '->retry(')),
            'throw=false keeps non-2xx responses returned (not thrown), matching the non-retry path.'
        );
    }

    public function test_non_send_data_paths_do_not_retry_by_default(): void
    {
        foreach (['muteUser', 'kickUser', 'initiateCall'] as $method) {
            $src = $this->methodSource($method);
            $this->assertStringNotContainsString(
                'send_data_retries',
                $src,
                "{$method} is not idempotent-safe to retry blindly; only send-data opts in."
            );
        }
    }
}
