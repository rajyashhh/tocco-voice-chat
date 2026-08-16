<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Gate for the 6 unauthenticated Quantum Nexus game webhooks
 * (mic-seats / user-info / sit-down / stand-up / game-start / game-end).
 *
 * This middleware does NOT touch the per-endpoint signature scheme — that
 * stays enforced inside NewLeaderCCGameController::verifySign() with the exact
 * param set & key per endpoint (provider wire compatibility is preserved).
 *
 * Responsibilities here:
 *   1. Panel-driven IP allowlist (CIDR + bare IP, IPv4/IPv6). Empty = allow all
 *      so nothing breaks before the owner fills the field.
 *   2. Replay/timestamp protection ONLY if the provider actually sends a usable
 *      timestamp + nonce. Per the integration map the current scheme sends
 *      neither, so this is a conservative no-op for live traffic and replay
 *      safety is provided by durable per-order idempotency in the controller.
 */
class VerifyQuantumWebhook
{
    /** Accepted clock skew for timestamp-based replay protection (seconds). */
    private const TIMESTAMP_WINDOW_SECONDS = 300;

    public function handle(Request $request, Closure $next)
    {
        $provider = Common::getByCode('utd');

        // No provider row / inactive => behave as before (let the controller's
        // own checks decide). We never hard-fail on missing config here to avoid
        // taking down live traffic on a panel mishap.
        if (!$provider) {
            return $next($request);
        }

        if (!$this->ipAllowed($request, $provider->ip_allowlist ?? null)) {
            // Provider's expected error shape for rejected calls.
            return response()->json(['errorCode' => 5009], 200);
        }

        if (!$this->timestampAndNonceOk($request)) {
            return response()->json(['errorCode' => 5009], 200);
        }

        return $next($request);
    }

    /**
     * @param string|null $allowlist comma/newline separated IPs and CIDR ranges
     */
    private function ipAllowed(Request $request, ?string $allowlist): bool
    {
        $entries = $this->parseAllowlist($allowlist);

        // Empty allowlist => allow all (backward-compatible).
        if (empty($entries)) {
            return true;
        }

        // TrustProxies is '*', so ->ip() is the real client IP from XFF.
        $clientIp = $request->ip();
        if (!$clientIp) {
            return false;
        }

        foreach ($entries as $entry) {
            if ($this->ipMatches($clientIp, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function parseAllowlist(?string $allowlist): array
    {
        if (!$allowlist) {
            return [];
        }

        $parts = preg_split('/[\s,]+/', trim($allowlist), -1, PREG_SPLIT_NO_EMPTY);

        return $parts ?: [];
    }

    private function ipMatches(string $clientIp, string $entry): bool
    {
        if (str_contains($entry, '/')) {
            return $this->cidrMatch($clientIp, $entry);
        }

        return inet_pton($clientIp) !== false
            && inet_pton($entry) !== false
            && inet_pton($clientIp) === inet_pton($entry);
    }

    private function cidrMatch(string $ip, string $cidr): bool
    {
        [$subnet, $maskLen] = explode('/', $cidr, 2);

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        // Must be the same family (both v4 or both v6) — pton length differs.
        if (strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $maskLen = (int) $maskLen;
        $maxBits = strlen($ipBin) * 8;
        if ($maskLen < 0 || $maskLen > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($maskLen, 8);
        $remBits = $maskLen % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
            return false;
        }

        if ($remBits === 0) {
            return true;
        }

        $mask = 0xFF << (8 - $remBits) & 0xFF;

        return (ord($ipBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask);
    }

    /**
     * Replay/timestamp gate. Active only when the provider sends BOTH a usable
     * timestamp and a nonce. Absent either, we do not fabricate a gate that
     * would break live traffic (idempotency in the controller is the safety net).
     */
    private function timestampAndNonceOk(Request $request): bool
    {
        $ts = $request->input('timestamp', $request->input('ts'));
        $nonce = $request->input('nonce');

        if ($ts === null || $nonce === null || $ts === '' || $nonce === '') {
            return true; // provider does not supply these — pass through.
        }

        if (!is_numeric($ts)) {
            return false;
        }

        // Provider may send seconds or milliseconds; normalize to seconds.
        $tsSeconds = (int) $ts;
        if ($tsSeconds > 9999999999) {
            $tsSeconds = (int) ($tsSeconds / 1000);
        }

        if (abs(time() - $tsSeconds) > self::TIMESTAMP_WINDOW_SECONDS) {
            return false;
        }

        // Single-use nonce within the window. Cache::add is atomic (SET NX):
        // a second occurrence of the same nonce fails to add => replay.
        $nonceKey = 'quantum_nonce_' . hash('sha256', (string) $nonce);
        if (!Cache::add($nonceKey, true, self::TIMESTAMP_WINDOW_SECONDS)) {
            return false;
        }

        return true;
    }
}
