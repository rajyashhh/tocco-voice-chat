<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Http\Controllers\Controller;
use App\Models\AllGame;
use App\Models\GameProcessedOrder;
use App\Models\GameSeat;
use App\Models\GameSession;
use App\Models\RewardWinnerGame;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;



class NewLeaderCCGameController extends Controller
{

    /**
     * UTD Games provider settings, resolved from the admin panel
     * (GameProviderSetting) — never from env/config. Cached by Common.
     */
    private function provider()
    {
        return Common::getByCode('utd');
    }

    /**
     * APP ID for LeaderCC (panel-managed).
     */
    private function appId(): string
    {
        return (string) ($this->provider()->app_id ?? '');
    }

    /**
     * Webhook signing key for the Game-Server -> App webhooks
     * (mic-seats/user-info/sit-down/stand-up/game-start/game-end).
     *
     * Per the official LeaderCC docs (item 709) these are signed as
     * md5(orderedParams + KEY).toUpperCase() where KEY is the shared secret —
     * i.e. our app_key, the SAME shared Key the live leader-cc-game/change-balance
     * routes verify with (see VerifyLeaderCCMiddleWare). So the default is app_key.
     *
     * 'app_id' / 'secret' (app_secret) are kept as legacy options for flexibility,
     * selectable via the panel's "webhook sign-key source".
     */
    private function signKey(): string
    {
        $provider = $this->provider();

        switch ($provider->webhook_sign_key_source ?? 'app_key') {
            case 'app_id':
                return (string) ($provider->app_id ?? '');
            case 'secret':
                return (string) ($provider->app_secret ?? '');
            case 'app_key':
            default:
                return (string) ($provider->app_key ?? '');
        }
    }

    /**
     * Generate a Sanctum token for the user to use in-game.
     * Token must remain valid for the entire game session.
     */
    private function generateToken(User $user): string
    {
        return $user->createToken('leader-cc-game')->plainTextToken;
    }



    /*
    |--------------------------------------------------------------------------
    | 1. Launch Game
    |--------------------------------------------------------------------------
    */
    public function urlGames(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'roomId' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'msg' => $validator->errors()->first()]);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 0, 'msg' => 'Unauthenticated.'], 401);
        }

        $room = Room::find((int)$request->roomId);
        if (!$room) {
            return response()->json(['status' => 0, 'msg' => 'Room not found.'], 404);
        }

        // Production game URL comes from the imported games list (all_games).
        // type=3 (Quantum Nexus) and type=4 (UTD) share the SAME launch mechanism —
        // UTD brokers the very same game company as Quantum, so its games are
        // launched by the identical URL/token flow. With a config fallback — never
        // the provider's test environment.
        $baseUrl = null;
        if ($request->filled('gameId')) {
            // Long-lived cache of the rarely-changing prod game URL, invalidated by
            // the AllGame observer on any row write. Avoids a per-launch DB hit.
            $gameId = $request->gameId;
            $baseUrl = Cache::rememberForever('allgame_url_' . $gameId, function () use ($gameId) {
                // Cache the resolved value (string|null). null is cached too so a
                // missing game does not re-query every launch; the observer clears
                // it the moment a matching row is created/updated.
                return AllGame::where('custom_id', $gameId)->whereIn('type', [3, 4])->value('url');
            });
        }
        $baseUrl = $baseUrl ?: $this->provider()->base_url;

        if (!$baseUrl) {
            return response()->json(['status' => 0, 'msg' => 'Game URL is not configured.'], 422);
        }

        $token = $this->generateToken($user);

        $url = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . http_build_query([
            'uid' => $user->id,
            'token' => $token,
            'lang' => app()->getLocale(),
            'roomid' => $request->roomId
        ]);

        return response()->json([
            'status' => 1,
            'gameUrl' => $url
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Verify Sign
    |--------------------------------------------------------------------------
    */
    private function generateSign(array $params)
    {
        return strtoupper(md5(implode('', $params) . $this->signKey()));
    }

    private function verifySign(array $params, $sign)
    {
        return hash_equals($this->generateSign($params), strtoupper((string) $sign));
    }

    /**
     * Durable idempotency claim for a provider order on a given endpoint.
     * Attempts to insert into game_processed_orders inside the current
     * transaction; the (endpoint, order_id) unique index is the source of
     * truth. Returns true if THIS call claimed the order (proceed with balance
     * movement), false if the order was already processed (replay — skip).
     */
    private function claimOrder(string $endpoint, string $orderId): bool
    {
        try {
            GameProcessedOrder::create([
                'endpoint'   => $endpoint,
                'order_id'   => $orderId,
                'created_at' => now(),
            ]);

            return true;
        } catch (QueryException $e) {
            // 23000 = integrity constraint violation (duplicate key) => replay.
            if ((int) $e->getCode() === 23000) {
                return false;
            }

            throw $e;
        }
    }

    /**
     * True when the exception is a duplicate-key / integrity violation (SQLSTATE
     * 23000) — used to translate the game_seats overbooking unique-index failure
     * into a clean "seat taken" response.
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        return (int) $e->getCode() === 23000;
    }






    public function usersUpMic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'     => 'required|integer|min:1',
            'roomId'  => 'required|integer|min:1',
            'orderId' => 'required|string|max:191',
            'gameId'  => 'required|max:191',
            'sign'    => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errorCode' => 5009]);
        }

        if (!$this->verifySign([
            $request->orderId,
            $request->gameId,
            $request->roomId,
            $request->uid
        ], $request->sign)) {
            return response()->json(['errorCode' => 5009]);
        }

        $room = Room::with('microphones')->find((int)$request->roomId);
        if (!$room) {
            return response()->json(['errorCode' => 4004]);
        }
        $usersUpMics = $room->microphones()->with('user')->where('status', 1)->get();

        return response()->json([
            'errorCode' => 0,
            'data' => [
                'list' =>
                $usersUpMics->map(function ($usersUpMic, $index) {
                    return [
                        "uid" => (string)$usersUpMic->user->id,
                        "location" => $usersUpMic->position, // أو seat لو عندك
                        "nickname" => $usersUpMic->user->name ?? '',
                        "avatar" => $usersUpMic->user->avatar ?? '',
                    ];
                })->values()


            ]
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | 3. Query User Info
    |--------------------------------------------------------------------------
    */
    public function userInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'    => 'required|integer|min:1',
            'gameId' => 'required|max:191',
            'sign'   => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errorCode' => 5009]);
        }

        if (!$this->verifySign([
            $request->gameId,
            $request->uid,
            $request->token
        ], $request->sign)) {
            return response()->json(['errorCode' => 5009]);
        }

        $user = User::find((int)$request->uid);

        if (!$user) {
            return response()->json(['errorCode' => 4002]);
        }

        return response()->json([
            'errorCode' => 0,
            'data' => [
                'uid' => $user->id,
                'nickname' => $user->name,
                'avatar' => $user->avatar
            ]
        ]);
    }




    /*
    |--------------------------------------------------------------------------
    | 4. Sit Down
    |--------------------------------------------------------------------------
    */
    public function sitDown(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'      => 'required|integer|min:1',
            'roomId'   => 'required|integer|min:1',
            'location' => 'required|integer|min:0',
            'orderId'  => 'required|string|max:191',
            'gameId'   => 'required|max:191',
            'fees'     => 'required|numeric|min:0',
            'sign'     => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errorCode' => 5009]);
        }

        if (!$this->verifySign([
            $request->orderId,
            $request->gameId,
            $request->roomId,
            $request->uid,
            $request->location
        ], $request->sign)) {
            return response()->json(['errorCode' => 5009]);
        }

        $roomId = (int)$request->roomId;
        $uid = (int)$request->uid;
        $fees = abs((float)$request->fees);

        // Fast-path replay check: an already-recorded seat for this order is a
        // success, never a second deduction. The durable game_processed_orders
        // unique index (claimed inside the transaction below) is the source of
        // truth; this read just avoids the lock for the common retry case.
        $alreadyProcessed = GameSeat::where('orderId', $request->orderId)
            ->where('game_id', $request->gameId)
            ->where('room_id', $roomId)
            ->where('user_id', $uid)
            ->exists();
        if ($alreadyProcessed) {
            return response()->json([
                'errorCode' => 0,
                'data' => 'success'
            ]);
        }

        if (!User::where('id', $uid)->exists()) {
            return response()->json(['errorCode' => 4002]);
        }

        // Seat must be free BEFORE any deduction
        $seatTaken = GameSeat::where('room_id', $roomId)
            ->where('game_id', $request->gameId)
            ->where('location', $request->location)
            ->whereNull('end_rank')
            ->exists();
        if ($seatTaken) {
            return response()->json(['errorCode' => 5007]);
        }

        // result: 'replay' | 'insufficient' | 'seat_taken' | float diAfter
        try {
            $result = DB::transaction(function () use ($roomId, $uid, $fees, $request) {
                // Durable idempotency: claim the order inside the transaction so a
                // concurrent replay either loses the unique race or is rolled back.
                if (!$this->claimOrder('sit_down', (string) $request->orderId)) {
                    return 'replay';
                }

                // Re-check the seat inside the transaction (the pre-transaction
                // check above is only a fast path). The authoritative guard is the
                // active_location unique index on game_seats — if two orders pass
                // this read concurrently, the loser's GameSeat::create throws a
                // QueryException caught below. Done before the debit so a taken seat
                // never deducts coins.
                $seatTaken = GameSeat::where('room_id', $roomId)
                    ->where('game_id', $request->gameId)
                    ->where('location', $request->location)
                    ->whereNull('end_rank')
                    ->lockForUpdate()
                    ->exists();
                if ($seatTaken) {
                    return 'seat_taken';
                }

                // Lock the user row, then atomically debit with bound parameters.
                $user = DB::table('users')->where('id', $uid)->lockForUpdate()->first(['di']);
                if (!$user || $user->di < $fees) {
                    return 'insufficient';
                }

                // Atomic decrement with bound parameter (no string concatenation).
                DB::table('users')->where('id', $uid)->decrement('di', $fees);

                GameSeat::create([
                    'room_id' => $roomId,
                    'user_id' => $uid,
                    'location' => $request->location,
                    'orderId' => $request->orderId,
                    'game_id' => $request->gameId,
                    'fees' => $fees,
                ]);

                $diAfter = (float) ($user->di - $fees);

                DB::afterCommit(function () use ($uid) {
                    // DB::table()->update() bypasses the User model observers.
                    Cache::forget("data_user_{$uid}");
                    Cache::forget("user_rooms_{$uid}");
                });

                return $diAfter;
            });
        } catch (QueryException $e) {
            // DB unique guard (game_seats_active_seat_unique) rejected a true
            // overbooking race: the seat was claimed concurrently after our read.
            // The whole transaction (including the debit) rolled back, so report
            // the seat as taken without any coin change.
            if ($this->isUniqueViolation($e)) {
                return response()->json(['errorCode' => 5007]);
            }
            throw $e;
        }

        if ($result === 'replay') {
            return response()->json([
                'errorCode' => 0,
                'data' => 'success'
            ]);
        }

        if ($result === 'seat_taken') {
            return response()->json(['errorCode' => 5007]);
        }

        if ($result === 'insufficient') {
            return response()->json(['errorCode' => 5204]);
        }

        UserCoinLogHelper::logByType(
            $uid,
            -$fees,
            $result + $fees,
            UserCoinLogType::COIN_GAME,
            null,
        );

        return response()->json([
            'errorCode' => 0,
            'data' => 'success'
        ]);
    }


    public function standUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'      => 'required|integer|min:1',
            'roomId'   => 'required|integer|min:1',
            'location' => 'required|integer|min:0',
            'orderId'  => 'required|string|max:191',
            'gameId'   => 'required|max:191',
            'sign'     => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errorCode' => 5009]);
        }

        if (!$this->verifySign([
            $request->orderId,
            $request->gameId,
            $request->roomId,
            $request->uid,
            $request->location
        ], $request->sign)) {
            return response()->json(['errorCode' => 5009]);
        }

        // Setting location = null is naturally idempotent (a replay is a no-op),
        // and standUp moves no balance, so no durable order claim is needed here.
        $roomId = (int)$request->roomId;
        $uid = (int)$request->uid;
        // 3. Remove user from seat
        GameSeat::where('room_id', $roomId)
            ->where('user_id', $uid)->where('orderId', $request->orderId)->where('game_id', $request->gameId)
            ->where('location', $request->location)
            ->update(['location' => null]);

        return response()->json([
            'errorCode' => 0,
            'data' => 'success'
        ]);
    }


    public function gameStart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'     => 'required|integer|min:1',
            'roomId'  => 'required|integer|min:1',
            'orderId' => 'required|string|max:191',
            'gameId'  => 'required|max:191',
            'sign'    => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errorCode' => 5009]);
        }

        if (!$this->verifySign([
            $request->orderId,
            $request->gameId,
            $request->roomId,
            $request->uid
        ], $request->sign)) {
            return response()->json(['errorCode' => 5009]);
        }

        // Durable idempotency: a replay of the same order must not create a
        // second GameSession row.
        $claimed = DB::transaction(function () use ($request) {
            if (!$this->claimOrder('game_start', (string) $request->orderId)) {
                return false;
            }

            Room::where('id', (int)$request->roomId)
                ->update(['game_id' => (int)$request->gameId]);

            GameSession::create([
                'game_id' => $request->gameId,
                'room_id' => $request->roomId,
                'owner_uid' => $request->uid,
                'started_at' => now(),
                'player_list' => $request->playerList,
            ]);

            return true;
        });

        return response()->json([
            'errorCode' => 0,
            'data' => 'success'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Game End
    |--------------------------------------------------------------------------
    */
    public function gameEnd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uid'        => 'required|integer|min:1',
            'roomId'     => 'required|integer|min:1',
            'orderId'    => 'required|string|max:191',
            'gameId'     => 'required|max:191',
            'sign'       => 'required|string',
            'rankList'   => 'nullable|array',
            'rankList.*' => 'integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json(['errorCode' => 5009]);
        }

        if (!$this->verifySign([
            $request->orderId,
            $request->gameId,
            $request->roomId,
            $request->uid
        ], $request->sign)) {
            return response()->json(['errorCode' => 5009]);
        }

        // Fast-path replay check (kept for cheapness); the durable
        // game_processed_orders unique index claimed inside the transaction is
        // the source of truth so reward distribution runs at most once.
        $orderKey = "webhook_order_{$request->orderId}";
        if (Cache::has($orderKey)) {
            return response()->json([
                'errorCode' => 0,
                'data' => 'success'
            ]);
        }

        $roomId = (int)$request->roomId;
        $rankList = (array)$request->rankList;

        $processed = DB::transaction(function () use ($request, $roomId, $rankList) {
            // Durable idempotency: claim first so a concurrent replay is rolled
            // back without crediting rewards twice.
            if (!$this->claimOrder('game_end', (string) $request->orderId)) {
                return false;
            }

            // Reward-per-rank from the long-lived cached map (rank => coins),
            // invalidated by RewardWinnerGameObserver on any config change. Same
            // resolved values as the previous whereIn, with no per-game-end query.
            $rewards = RewardWinnerGame::rewardMap();

            $creditedUids = [];

            foreach ($rankList as $index => $uid) {
                $rank = $index + 1;
                if (!array_key_exists($rank, $rewards)) {
                    continue;
                }
                $rewardCoins = $rewards[$rank];

                $uid = (int) $uid;

                // Lock the winner row, credit atomically with a bound parameter.
                $row = DB::table('users')->where('id', $uid)->lockForUpdate()->first(['di']);
                if (!$row) {
                    continue;
                }

                $diBefore = (float) $row->di;
                DB::table('users')->where('id', $uid)->increment('di', $rewardCoins);

                UserCoinLogHelper::logByType(
                    $uid,
                    $rewardCoins,
                    $diBefore,
                    UserCoinLogType::COIN_GAME,
                    null,
                );

                // Seats were created in sitDown with their own orderId/location —
                // the game-end webhook carries neither, so match on room/user/game only.
                GameSeat::where('room_id', $roomId)
                    ->where('user_id', $uid)->where('game_id', $request->gameId)
                    ->whereNull('end_rank')->update([
                        'end_rank' => $rank,
                        'coin_reward' => $rewardCoins,
                    ]);

                $creditedUids[] = $uid;
            }

            $gameSession = GameSession::where([
                'game_id' => $request->gameId,
                'room_id' => $request->roomId,
                'owner_uid' => $request->uid,
            ])->whereNull('ended_at')->first();
            if ($gameSession) {
                $gameSession->update([
                    'ended_at' => now(),
                    'reason' => $request->reason,
                    'rankList' => $request->rankList,
                    'room_destroy' => (bool)$request->input('roomDestroy', false),
                ]);
            }

            DB::afterCommit(function () use ($creditedUids) {
                // increment() bypasses the User model observers.
                foreach ($creditedUids as $uid) {
                    Cache::forget("data_user_{$uid}");
                    Cache::forget("user_rooms_{$uid}");
                }
            });

            return true;
        });

        if ($processed) {
            Cache::put($orderKey, true, now()->addMinutes(30));
        }

        return response()->json([
            'errorCode' => 0,
            'data' => 'success'
        ]);
    }
}
