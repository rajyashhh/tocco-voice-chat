<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\Common;
use App\Jobs\AllOpeningRoomsZegoRequest;
use App\Models\Room;
use App\Models\User;
use App\Models\GameWallet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\Helpers\UserCoinLogHelper;
use App\Enums\UserCoinLogType;

class LeaderCCgameController extends Controller
{

    private function json($errorCode = 0, $message = 'success', $data = [])
    {
        return response()->json([
            'errorCode' => $errorCode,
            'errorMsg'  => $message,
            'data'      => $data
        ]);
    }

    private function safe(callable $fn)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            Log::error("LeaderCC Error: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->json(500, 'Server error' . $e->getMessage());
        }
    }


    public function userInformation(Request $request)
    {
        return $this->safe(function () use ($request) {

            if (!$request->uid || !$request->gameId || !$request->token) {
                return $this->json(4005, 'Missing parameters');
            }

            if ($err = $this->checkWallet($request)) {
                return $err;
            }

            $user = User::with(['profile:id,user_id,avatar', 'UserVip:id,user_id,level'])
                ->select('id', 'name', 'di')
                ->find($request->uid);

            if (!$user) {
                return $this->json(4005, 'user not found');
            }

            return $this->json(0, 'success', [
                'uid'      => $user->id,
                'nickname' => $user->name,
                'avatar'   => getImagePath($user->profile->avatar),
                'coin'     => $user->di,
                'vipLevel' => $user->UserVip->level ?? 0,
                'water' => @$user->gamePercentage->percentageGame->percentage_game ?? 2.00,


            ]);
        });
    }


    public function updateGameCoin(Request $request)
    {
        return $this->safe(function () use ($request) {

            $required = ['orderId', 'gameId', 'roundId', 'uid', 'coin', 'type', 'rewardType', 'token', 'sign'];
            $missing = array_filter($required, fn($r) => !$request->filled($r) && $request->input($r) !== "0");
            if ($missing) return $this->json(4005, 'Invalid params');

            $type = (int)$request->type;
            if (!in_array($type, [1, 2])) {
                return $this->json(4005, 'Invalid type');
            }

            $uid      = (int) $request->uid;
            $coin     = abs((int) $request->coin);
            $orderKey = "order_{$request->orderId}";

            // Idempotency guard — already processed (e.g. game retried a 504'd-but-committed request)
            if (Cache::has($orderKey)) {
                $di = DB::table('users')->where('id', $uid)->value('di');
                return $di === null
                    ? $this->json(4005, 'User not found')
                    : $this->json(0, 'success', ['coins' => (int) $di]);
            }

            if ($err = $this->checkWallet($request)) {
                return $err;
            }

            // Atomic balance change + ledger insert in a short transaction (no row lock held across I/O)
            $result = DB::transaction(function () use ($uid, $coin, $type, $request) {
                if ($type == 1) {
                    $affected = DB::table('users')
                        ->where('id', $uid)
                        ->where('di', '>=', $coin)
                        ->update(['di' => DB::raw('di - ' . $coin)]);
                    if ($affected === 0) {
                        $exists = DB::table('users')->where('id', $uid)->exists();
                        return ['err' => $exists ? [4004, 'Insufficient game coins'] : [4005, 'User not found']];
                    }
                } else {
                    $affected = DB::table('users')
                        ->where('id', $uid)
                        ->update([
                            'di'       => DB::raw('di + ' . $coin),
                            'new_gift' => 1,
                        ]);
                    if ($affected === 0) {
                        return ['err' => [4005, 'User not found']];
                    }
                }

                DB::table('coin_game_users')->insert([
                    'user_id'          => $uid,
                    'coins'            => $coin,
                    'app_profit_coins' => $coin,
                    'type'             => $type == 1 ? 0 : 1,
                    'game_id'          => $request->gameId,
                    'round_id'         => $request->roundId,
                    'order_id'         => $request->orderId,
                    'created_at'       => now(),
                    'updated_at'       => now()
                ]);

                $row = DB::table('users')->where('id', $uid)->first(['di', 'agency_id']);
                return ['after' => (int) $row->di, 'agency_id' => $row->agency_id];
            });

            if (isset($result['err'])) {
                return $this->json($result['err'][0], $result['err'][1]);
            }

            $amountAfter  = $result['after'];
            $amountBefore = $type == 1 ? ($amountAfter + $coin) : ($amountAfter - $coin);

            // Coin ledger (after commit, outside the lock window)
            UserCoinLogHelper::logByType(
                $uid,
                $type == 1 ? -$coin : $coin,
                $amountBefore,
                UserCoinLogType::COIN_GAME,
                null,
            );

            // Manual cache invalidation — DB::table()->update() bypasses the User model observers
            Cache::forget("data_user_{$uid}");
            Cache::forget("user_rooms_{$uid}");
            if (!empty($result['agency_id'])) {
                clearAgencyCache($result['agency_id']);
            }

            // Feed the real-time Redis leaderboard on wins (request type == 2 ->
            // coin_game_users.type == 1). Never let ranking bookkeeping fail the
            // wallet path; game:backfill-ranking reconciles any miss within 15 min.
            if ($type == 2) {
                try {
                    app(\App\Services\GameRankingService::class)->recordPlay($uid, $coin);
                } catch (\Throwable $e) {
                    Log::warning('GameRanking recordPlay failed', ['uid' => $uid, 'err' => $e->getMessage()]);
                }
            }

            Cache::put($orderKey, true, now()->addMinutes(30));

            dispatch(new \App\Jobs\GameWalletJop($type == 1 ? -$coin : $coin));

            // Broadcast big wins (rare) — eager loads only here, outside the critical path
            $gameMapWinCoins = Common::getConfig('game_map_win_coins') ?? 10000;
            if ($type == 2 && $coin >= $gameMapWinCoins) {
                $user = User::with([
                    'profile:id,user_id,avatar',
                    'nowGame:id,image',
                    'nowRoom:id,uid'
                ])->find($uid);

                if ($user) {
                    $d = [
                        "messageContent" => [
                            "message" => "SBG",
                            "event"   => "game.win.event",
                            'uImage'  => $user->profile?->avatar ?? 0,
                            'uName'   => $user->name ?? '',
                            'uId'     => $user->id ?? 0,
                            'coins'   => numToStringNew($coin),
                            "gImage"  => @$user->nowGame?->image
                        ]
                    ];

                    dispatchJobToQueue(new AllOpeningRoomsZegoRequest(json_encode($d), $user->id, $user->nowRoom?->id, false), 'heavyProcessing');
                }
            }

            return $this->json(0, 'success', [
                'coins' => $amountAfter
            ]);
        });
    }


    public function makeUpOrders(Request $request)
    {
        return $this->safe(function () use ($request) {

            $validator = Validator::make($request->all(), [
                'orderId'     => 'required',
                'gameId'      => 'required',
                'roundId'     => 'required',
                'uid'         => 'required',
                'coin'        => 'required|numeric',
                'rewardType'  => 'required|integer',
                'sign'        => 'required'
            ]);

            if ($validator->fails()) {
                return $this->json(4005, 'Missing or invalid parameters', $validator->errors());
            }

            $uid      = (int) $request->uid;
            $coin     = abs((int) $request->coin);
            $orderKey = "order_{$request->orderId}";

            if (Cache::has($orderKey)) {
                $di = DB::table('users')->where('id', $uid)->value('di');
                return $this->json(0, 'success', ['coin' => (int) ($di ?? 0)]);
            }

            $result = DB::transaction(function () use ($uid, $coin, $request) {
                $affected = DB::table('users')
                    ->where('id', $uid)
                    ->update([
                        'di'       => DB::raw('di + ' . $coin),
                        'new_gift' => 1,
                    ]);
                if ($affected === 0) {
                    return null;
                }

                DB::table('coin_game_users')->insert([
                    'user_id'    => $uid,
                    'coins'      => $coin,
                    'app_profit_coins' => $coin,
                    'type'       => 1,
                    'game_id'    => $request->gameId,
                    'round_id'   => $request->roundId,
                    'order_id'   => $request->orderId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $row = DB::table('users')->where('id', $uid)->first(['di', 'agency_id']);
                return ['after' => (int) $row->di, 'agency_id' => $row->agency_id];
            });

            if ($result === null) {
                return $this->json(4005, 'user not found');
            }

            $amountAfter = $result['after'];
            UserCoinLogHelper::logByType(
                $uid,
                $coin,
                $amountAfter - $coin,
                UserCoinLogType::COIN_GAME,
                null,
            );

            Cache::forget("data_user_{$uid}");
            Cache::forget("user_rooms_{$uid}");
            if (!empty($result['agency_id'])) {
                clearAgencyCache($result['agency_id']);
            }

            // makeUpOrders always inserts a coin_game_users row with type=1 (win) —
            // feed the real-time Redis leaderboard (failure here must never fail
            // the wallet path; the 15-min reconcile covers any miss).
            try {
                app(\App\Services\GameRankingService::class)->recordPlay($uid, $coin);
            } catch (\Throwable $e) {
                Log::warning('GameRanking recordPlay failed', ['uid' => $uid, 'err' => $e->getMessage()]);
            }

            Cache::put($orderKey, true, now()->addHour());
            dispatch(new \App\Jobs\GameWalletJop($coin));

            return $this->json(0, 'success', ['coin' => $amountAfter]);
        });
    }




    public function checkWallet($request)
    {
        if ($request->type == 1 && $this->checkLoseWallet($request->coin)) {
            return $this->json(4005, 'game not available');
        }
        return null;
    }

    public function checkLoseWallet(float $coins): bool
    {
        // 60s cache keeps the per-bet wallet query off the hot path; the monthly
        // loss cap tolerates that staleness. currentMonth() also auto-opens the
        // new month's row instead of closing all games on month rollover.
        $wallet = Cache::remember(
            'game_wallet_' . now()->format('Y-m'),
            60,
            fn () => GameWallet::currentMonth()
        );

        return ($wallet->used + $coins) >= $wallet->balance;
    }

}