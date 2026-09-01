<?php

namespace App\Services;



/**
 * Generates Agora RTC Access Tokens (version 007 / AccessToken2).
 *
 * This is a self-contained implementation of the Agora AccessToken2 spec.
 * It requires ONLY ext-hash (HMAC-SHA256), which is bundled with every
 * standard PHP installation. No Composer packages are added.
 *
 * UID mapping contract (documented for the Flutter team):
 * ─────────────────────────────────────────────────────
 *   Agora integer UID  =  (int) $user->id
 *
 * Agora UIDs are uint32 (1 … 2³²−1).  The app's `users.id` is an
 * auto-incrementing bigint (MySQL BIGINT UNSIGNED).  Because user IDs
 * are kept well below 2³³ (~8.5 billion) in practice, the cast is safe.
 * If a user id ever exceeds the uint32 range, this class will reject
 * it with a logged warning and return null.
 *
 * @see https://docs.agora.io/en/realtime-media/im/build/secure-access-and-authentication/access-token-2
 */
class AgoraTokenService
{
    private string $appId;
    private string $appCertificate;
    private int    $tokenExpiry;

    public function __construct(
        ?string $appId = null,
        ?string $appCertificate = null,
        ?int    $tokenExpiry = null
    ) {
        $this->appId          = $appId ?? (string) config('agora.app_id', '');
        $this->appCertificate = $appCertificate ?? (string) config('agora.app_certificate', '');
        $this->tokenExpiry    = $tokenExpiry ?? (int) config('agora.token_expiry', 3600);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Generate an RTC token for the given user and channel.
     *
     * @param  int    $userId      The users.id primary key (mapped to uint32 uid).
     * @param  string $channelName The room / channel name.
     * @param  string|null $role   'host' / 'admin' / 'guest' / 'audience' / 'visitor'.
     *                              host / admin → PUBLISHER (1); everything else → SUBSCRIBER (2).
     *                              null defaults to PUBLISHER.
     * @param  int|null   $expireOverride  Token lifetime in seconds (null = config default).
     * @return array{token: string, uid: int, expires_at: int}|null
     */
    public function generateRtcToken(
        int $userId,
        string $channelName,
        ?string $role = null,
        ?int $expireOverride = null
    ): ?array {
        if (empty($this->appId) || empty($this->appCertificate)) {
            error_log('AgoraTokenService: AGORA_APP_ID or AGORA_APP_CERTIFICATE is not configured.');
            return null;
        }

        $uid = $this->mapUserIdToUid($userId);
        if ($uid === null) {
            return null;
        }

        $roleValue   = $this->resolveRole($role);
        $tokenExpiry = $expireOverride ?? $this->tokenExpiry;
        $issueTs     = time();
        $salt        = random_int(1, 99999999);
        $expireTs    = $issueTs + $tokenExpiry;

        $token = $this->buildAccessToken2(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $uid,
            $roleValue,
            $tokenExpiry,
            $issueTs,
            $salt
        );

        if ($token === '') {
            error_log('AgoraTokenService: token build returned empty string. user_id=' . $userId . ' channel=' . $channelName);
            return null;
        }

        return [
            'token'     => $token,
            'uid'       => $uid,
            'expires_at' => $expireTs,
        ];
    }

    /**
     * Check whether the service has valid credentials configured.
     */
    public function isConfigured(): bool
    {
        return $this->appId !== '' && $this->appCertificate !== '';
    }

    // ──────────────────────────────────────────────────────────────────────
    //  UID mapping
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Map users.id (bigint) → Agora uint32 UID.
     *
     * Deterministic: the same users.id always maps to the same UID.
     * Agora UIDs are uint32 (1 to 2³² − 1 = 4,294,967,295).
     * users.id is BIGINT UNSIGNED; values above 2³² are rejected.
     *
     * @return int|null  A 32-bit unsigned integer, or null on overflow.
     */
    public function mapUserIdToUid(int $userId): ?int
    {
        if ($userId <= 0 || $userId > 4_294_967_295) {
            error_log('AgoraTokenService: users.id out of uint32 range. user_id=' . $userId);
            return null;
        }

        return $userId;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Role mapping
    // ──────────────────────────────────────────────────────────────────────

    private const ROLE_PUBLISHER  = 1;
    private const ROLE_SUBSCRIBER = 2;

    /**
     * Map the existing room-role strings to Agora role integers.
     *
     *   host, admin  → PUBLISHER  (can publish audio + video + data)
     *   guest, audience, visitor → SUBSCRIBER  (receive-only by default)
     *
     * null / unrecognized → PUBLISHER (safe default; hosts always need it).
     */
    private function resolveRole(?string $role): int
    {
        if ($role === null) {
            return self::ROLE_PUBLISHER;
        }

        return in_array(strtolower($role), ['guest', 'audience', 'visitor'], true)
            ? self::ROLE_SUBSCRIBER
            : self::ROLE_PUBLISHER;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  AccessToken2 (007) — Agora-spec implementation
    // ──────────────────────────────────────────────────────────────────────

    private const TOKEN_VERSION = '007';
    private const SERVICE_TYPE_RTC = 1;

    private const PRIVILEGE_JOIN_CHANNEL       = 1;
    private const PRIVILEGE_PUBLISH_AUDIO      = 2;
    private const PRIVILEGE_PUBLISH_VIDEO      = 3;
    private const PRIVILEGE_PUBLISH_DATA_STREAM = 4;

    /**
     * Build an AccessToken2 (007) token for the RTC service.
     *
     * @see https://github.com/AgoraIO/Tools/blob/master/DynamicKey/AgoraDynamicKey/php/src/RtcTokenBuilder2.php
     */
    private function buildAccessToken2(
        string $appId,
        string $appCertificate,
        string $channelName,
        int    $uid,
        int    $role,
        int    $tokenExpire,
        int    $issueTs,
        int    $salt
    ): string {
        // ── Derive the signing key ──────────────────────────────────────
        // signing = HMAC-SHA256(appCertificate, pack(issueTs)) → HMAC-SHA256(_, pack(salt))
        $signingKey = hash_hmac('sha256', $appCertificate, self::packUint32($issueTs), true);
        $signingKey = hash_hmac('sha256', $signingKey, self::packUint32($salt), true);

        // ── Build the service payload ───────────────────────────────────
        // Privileges use *relative* expiry (seconds from now), matching
        // the Agora reference.  The token-level expiry is the absolute
        // window; the privilege-level expiry is 0 (inherit token expiry).
        $privileges = [
            self::PRIVILEGE_JOIN_CHANNEL => $tokenExpire,
        ];

        if ($role === self::ROLE_PUBLISHER) {
            $privileges[self::PRIVILEGE_PUBLISH_AUDIO]       = $tokenExpire;
            $privileges[self::PRIVILEGE_PUBLISH_VIDEO]       = $tokenExpire;
            $privileges[self::PRIVILEGE_PUBLISH_DATA_STREAM] = $tokenExpire;
        }

        $serviceData = self::packUint16(self::SERVICE_TYPE_RTC)
            . self::packMapUint32($privileges)
            . self::packString($channelName)
            . self::packString((string) $uid);

        // ── Assemble the full token payload ─────────────────────────────
        $data = self::packString($appId)
            . self::packUint32($issueTs)
            . self::packUint32($tokenExpire)
            . self::packUint32($salt)
            . self::packUint16(1)              // service count (always 1 for RTC)
            . $serviceData;

        // ── Sign & encode ───────────────────────────────────────────────
        $signature = hash_hmac('sha256', $data, $signingKey, true);

        return self::TOKEN_VERSION . base64_encode(
            zlib_encode(
                self::packString($signature) . $data,
                ZLIB_ENCODING_DEFLATE
            )
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    //  Binary packing helpers (little-endian, Agora spec)
    // ──────────────────────────────────────────────────────────────────────

    private static function packUint16(int $x): string
    {
        return pack('v', $x);
    }

    private static function packUint32(int $x): string
    {
        return pack('V', $x);
    }

    /**
     * Pack a string with a uint16 length prefix.
     */
    private static function packString(string $str): string
    {
        return self::packUint16(strlen($str)) . $str;
    }

    /**
     * Pack an integer map as: uint16(count) + (uint16(key) + uint32(value))*
     * Keys are sorted ascending per the Agora spec.
     */
    private static function packMapUint32(array $map): string
    {
        ksort($map);
        $kv = '';
        foreach ($map as $key => $val) {
            $kv .= self::packUint16((int) $key) . self::packUint32((int) $val);
        }
        return self::packUint16(count($map)) . $kv;
    }
}
