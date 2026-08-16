<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Games\QuantumNexusClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * App -> Game control endpoints for Quantum Nexus INTERACTIVE (mic-seat) games.
 *
 * The room owner drives the game from inside the room (Games room mode): list
 * the available interactive games, open one in the room (create room + start),
 * and end/close/restart it. The per-player seat flow (sit-down/stand-up/result)
 * is handled by the Game -> App webhooks in NewLeaderCCGameController.
 *
 * All provider config (appId/app_key/base_url/endpoint URLs) is panel-driven via
 * GameProviderSetting; nothing here is hardcoded. See QuantumNexusClient.
 */
class InteractiveGameController extends Controller
{
    public function __construct(private QuantumNexusClient $client)
    {
    }

    /** Available interactive games (for the in-room Games-mode picker). */
    public function gameList()
    {
        $res = $this->client->getGameList();

        if (! $res['ok']) {
            return Common::apiResponse(0, $res['error'] ?? __('api_responses.failed'));
        }

        return Common::apiResponse(1, __('api_responses.success'), $res['data']);
    }

    /**
     * Open an interactive game in the caller's room: create the game room then
     * start it. Only the room owner may open a game in their room.
     */
    public function open(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required',
            'game_id' => 'required|string',
            'mode'    => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return Common::apiResponse(0, __('api_responses.invalid_data'), $validator->errors());
        }

        $room = $this->ownedRoomOrNull($request->room_id);
        if (! $room) {
            return Common::apiResponse(0, __('api_responses.you_dont_have_permission'));
        }

        $user = $request->user();
        $roomId = (string) $room->id;
        $gameId = (string) $request->game_id;

        // First userInfos entry MUST be the room owner and MUST NOT be a bot (ai=0).
        $owner = [
            'uid'      => (string) $user->id,
            'nickname' => (string) ($user->name ?? ''),
            'avatar'   => (string) ($user->avatar ?? ''),
            'ai'       => 0,
        ];

        $created = $this->client->createRoom($gameId, $roomId, [$owner]);
        if (! $created['ok']) {
            return Common::apiResponse(0, $created['error'] ?? __('api_responses.failed'), ['errorCode' => $created['errorCode']]);
        }

        $started = $this->client->startGame($gameId, $roomId, (string) ($request->mode ?? ''));
        if (! $started['ok']) {
            return Common::apiResponse(0, $started['error'] ?? __('api_responses.failed'), ['errorCode' => $started['errorCode']]);
        }

        return Common::apiResponse(1, __('api_responses.success'), [
            'room_id' => $roomId,
            'game_id' => $gameId,
        ]);
    }

    /** End the current game in the caller's room (room stays open). */
    public function end(Request $request)
    {
        [$room, $gameId, $err] = $this->roomAndGame($request);
        if ($err) {
            return $err;
        }

        $res = $this->client->endGame((string) $room->id, $gameId);
        return $this->respond($res);
    }

    /** Close the game room entirely. */
    public function close(Request $request)
    {
        $room = $this->ownedRoomOrNull($request->room_id);
        if (! $room) {
            return Common::apiResponse(0, __('api_responses.you_dont_have_permission'));
        }

        $res = $this->client->closeRoom((string) $room->id);
        return $this->respond($res);
    }

    /** Force restart / switch the game. */
    public function restart(Request $request)
    {
        [$room, $gameId, $err] = $this->roomAndGame($request);
        if ($err) {
            return $err;
        }

        $res = $this->client->restartGame((string) $room->id, $gameId);
        return $this->respond($res);
    }

    /** Current game-room state. */
    public function roomInfo(Request $request)
    {
        $room = $this->ownedRoomOrNull($request->room_id);
        if (! $room) {
            return Common::apiResponse(0, __('api_responses.you_dont_have_permission'));
        }

        $res = $this->client->getRoomInfo((string) $room->id);
        return $this->respond($res);
    }

    /* -------------------------------------------------------------------- */

    private function ownedRoomOrNull($roomId): ?Room
    {
        if (! $roomId) {
            return null;
        }

        $room = Room::find($roomId);
        if (! $room) {
            return null;
        }

        return (int) $room->uid === (int) request()->user()->id ? $room : null;
    }

    /** Validate room ownership + game_id; return [room, gameId, errorResponse|null]. */
    private function roomAndGame(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required',
            'game_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return [null, null, Common::apiResponse(0, __('api_responses.invalid_data'), $validator->errors())];
        }

        $room = $this->ownedRoomOrNull($request->room_id);
        if (! $room) {
            return [null, null, Common::apiResponse(0, __('api_responses.you_dont_have_permission'))];
        }

        return [$room, (string) $request->game_id, null];
    }

    private function respond(array $res)
    {
        if (! $res['ok']) {
            return Common::apiResponse(0, $res['error'] ?? __('api_responses.failed'), ['errorCode' => $res['errorCode']]);
        }

        return Common::apiResponse(1, __('api_responses.success'), $res['data']);
    }
}
