<?php

namespace App\Services\Games;

use App\Helpers\Common;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * App -> Game-server client for Quantum Nexus (LeaderCC) INTERACTIVE games
 * (mic-seat games: Ludo, etc.). This is the outbound direction: the App calls
 * the game server to list games, create rooms, start/end/close games and query
 * room state. The inbound direction (Game -> App webhooks: mic-seats, sit-down,
 * game-start, ...) lives in NewLeaderCCGameController.
 *
 * EVERYTHING is panel-driven (GameProviderSetting, code 'quantum_nexus'):
 *   - app_id   : Platform APPID, sent as a normal request parameter.
 *   - app_key  : the shared Key. NEVER transmitted — only appended when signing.
 *   - base_url : root of the game server (used to build each endpoint URL).
 *   - extra_settings['app_game_endpoints'] : optional per-interface URL overrides.
 *
 * Signing (per LeaderCC docs, item 663362709 "Server Access description"):
 *   sign = strtoupper(md5( <ordered params> + Key ))
 * The parameter ORDER differs per interface and is reproduced exactly below.
 * The Key is the shared app_key (NOT app_id), matching the existing single-player
 * games and the Game->App webhooks.
 *
 * The actual request URL of each interface is provider-specific ("each interface
 * requires providing the corresponding request URL"), so each endpoint resolves
 * from the panel: extra_settings['app_game_endpoints'][<key>] if set, else
 * rtrim(base_url,'/') + <default relative path>. No URL or secret is hardcoded.
 */
class QuantumNexusClient
{
    /** Default relative paths under base_url; overridable per-endpoint from the panel. */
    private const DEFAULT_PATHS = [
        'game_list'    => '/api/game/list',
        'room_list'    => '/api/room/list',
        'create_room'  => '/api/room/create',
        'start_game'   => '/api/game/start',
        'end_game'     => '/api/game/end',
        'close_room'   => '/api/room/close',
        'room_info'    => '/api/room/info',
        'user_exit'    => '/api/room/exit',
        'restart_game' => '/api/game/restart',
    ];

    private function provider()
    {
        return Common::getByCode('utd');
    }

    private function appId(): string
    {
        return (string) ($this->provider()->app_id ?? '');
    }

    private function key(): string
    {
        return (string) ($this->provider()->app_key ?? '');
    }

    private function isActive(): bool
    {
        $p = $this->provider();
        return $p && (int) ($p->is_active ?? 0) === 1;
    }

    /** strtoupper(md5(orderedValues + key)) — values already concatenated in order. */
    private function sign(string $orderedValues): string
    {
        return strtoupper(md5($orderedValues . $this->key()));
    }

    /**
     * Resolve the full request URL for an interface: panel override first,
     * else base_url + default path.
     */
    private function endpoint(string $name): ?string
    {
        $p = $this->provider();
        if (! $p) {
            return null;
        }

        $extra = $p->extra_settings ?? [];
        if (is_string($extra)) {
            $extra = json_decode($extra, true) ?: [];
        }
        $overrides = $extra['app_game_endpoints'] ?? [];

        if (! empty($overrides[$name])) {
            return (string) $overrides[$name];
        }

        $base = rtrim((string) ($p->base_url ?? ''), '/');
        if ($base === '' || ! isset(self::DEFAULT_PATHS[$name])) {
            return null;
        }

        return $base . self::DEFAULT_PATHS[$name];
    }

    /**
     * POST a signed JSON payload to an interface and return the decoded body.
     * Returns ['ok' => bool, 'errorCode' => int|null, 'data' => mixed, 'error' => string|null].
     */
    private function call(string $name, array $params): array
    {
        if (! $this->isActive()) {
            return $this->fail('provider_inactive', 'Quantum Nexus is not active');
        }
        if ($this->appId() === '' || $this->key() === '') {
            return $this->fail('not_configured', 'appId/app_key not configured in panel');
        }

        $url = $this->endpoint($name);
        if (! $url) {
            return $this->fail('no_endpoint', "endpoint URL for '{$name}' not configured (set base_url or extra_settings.app_game_endpoints)");
        }

        try {
            $response = Http::asJson()
                ->timeout(10)
                ->retry(2, 200)
                ->post($url, $params);
        } catch (\Throwable $e) {
            Log::warning('QuantumNexus app->game call failed', ['endpoint' => $name, 'error' => $e->getMessage()]);
            return $this->fail('http_error', $e->getMessage());
        }

        $body = $response->json();
        if (! is_array($body)) {
            return $this->fail('bad_response', 'non-JSON response (HTTP ' . $response->status() . ')');
        }

        $errorCode = $body['errorCode'] ?? $body['error_code'] ?? null;

        return [
            'ok'        => $response->successful() && (int) $errorCode === 0,
            'errorCode' => $errorCode,
            'data'      => $body['data'] ?? null,
            'error'     => null,
        ];
    }

    private function fail(string $code, string $message): array
    {
        return ['ok' => false, 'errorCode' => null, 'data' => null, 'error' => $code . ': ' . $message];
    }

    /*
    |--------------------------------------------------------------------------
    | Interfaces (App -> Game). Signature param order per LeaderCC docs.
    |--------------------------------------------------------------------------
    */

    /** 3.1 Get game list — sign md5(appId + key). */
    public function getGameList(): array
    {
        $appId = $this->appId();
        return $this->call('game_list', [
            'appId' => $appId,
            'sign'  => $this->sign($appId),
        ]);
    }

    /** 3.2 Get room list — sign md5(appId + gameId + key). */
    public function getRoomList(string $gameId): array
    {
        $appId = $this->appId();
        return $this->call('room_list', [
            'appId'  => $appId,
            'gameId' => $gameId,
            'sign'   => $this->sign($appId . $gameId),
        ]);
    }

    /**
     * Quick-Mode create room — sign md5(appId + gameId + roomId + key).
     * $userInfos: [['uid'=>, 'nickname'=>, 'avatar'=>, 'ai'=>0|1|2|3], ...]
     * First entry is the room owner and MUST NOT be a bot (ai=0).
     */
    public function createRoom(string $gameId, string $roomId, array $userInfos): array
    {
        $appId = $this->appId();
        return $this->call('create_room', [
            'appId'     => $appId,
            'gameId'    => $gameId,
            'roomId'    => $roomId,
            'userInfos' => array_values($userInfos),
            'sign'      => $this->sign($appId . $gameId . $roomId),
        ]);
    }

    /** Start game — sign md5(appId + gameId + roomId + mode + key). */
    public function startGame(string $gameId, string $roomId, string $mode = ''): array
    {
        $appId = $this->appId();
        return $this->call('start_game', [
            'appId'  => $appId,
            'gameId' => $gameId,
            'roomId' => $roomId,
            'mode'   => $mode,
            'sign'   => $this->sign($appId . $gameId . $roomId . $mode),
        ]);
    }

    /** 3.3 End game — sign md5(appId + roomId + gameId + key). */
    public function endGame(string $roomId, string $gameId): array
    {
        $appId = $this->appId();
        return $this->call('end_game', [
            'appId'  => $appId,
            'roomId' => $roomId,
            'gameId' => $gameId,
            'sign'   => $this->sign($appId . $roomId . $gameId),
        ]);
    }

    /** 3.4 Close room — sign md5(appId + roomId + key). */
    public function closeRoom(string $roomId): array
    {
        $appId = $this->appId();
        return $this->call('close_room', [
            'appId'  => $appId,
            'roomId' => $roomId,
            'sign'   => $this->sign($appId . $roomId),
        ]);
    }

    /** 3.6 Get room info — sign md5(appId + roomId + key). */
    public function getRoomInfo(string $roomId): array
    {
        $appId = $this->appId();
        return $this->call('room_info', [
            'appId'  => $appId,
            'roomId' => $roomId,
            'sign'   => $this->sign($appId . $roomId),
        ]);
    }

    /** 3.5 User exits room — sign md5(appId + roomId + uid + key). */
    public function userExitRoom(string $roomId, string $uid): array
    {
        $appId = $this->appId();
        return $this->call('user_exit', [
            'appId'  => $appId,
            'roomId' => $roomId,
            'uid'    => $uid,
            'sign'   => $this->sign($appId . $roomId . $uid),
        ]);
    }

    /** 3.7 Force restart / switch — sign md5(appId + roomId + gameId + key). */
    public function restartGame(string $roomId, string $gameId): array
    {
        $appId = $this->appId();
        return $this->call('restart_game', [
            'appId'  => $appId,
            'roomId' => $roomId,
            'gameId' => $gameId,
            'sign'   => $this->sign($appId . $roomId . $gameId),
        ]);
    }
}
