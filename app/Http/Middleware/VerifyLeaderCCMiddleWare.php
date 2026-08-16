<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use App\Helpers\LogHelper;
use App\Models\GameProviderSetting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class VerifyLeaderCCMiddleWare
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        try {
         
            $path = ltrim(str_replace('api/', '', $request->path()), '/');

            // Multiple game providers (Quantum Nexus + UTD) share this same
            // /leader-cc-game/* callback surface. Each has its OWN app_key, so we
            // collect the key of every ACTIVE provider and later accept the request
            // if its signature matches ANY of them — the signature itself proves
            // which provider called us. getByCode() returns null when a provider
            // row doesn't exist, so guard explicitly (no @ silencer).
            $activeKeys = [];
            foreach (['quantum_nexus', 'utd'] as $code) {
                $p = Common::getByCode($code);
                if ($p && $p->is_active && !empty($p->app_key)) {
                    $activeKeys[$code] = $p->app_key;
                }
            }

            if (empty($activeKeys)) {
                return response()->json([
                    'errorCode' => 4005,
                    'errorMsg'  => 'Game is not active now',
                ]);
            }

            // Duplicate orders are handled idempotently in the controller (returns success + balance),
            // so the game never treats a retried-but-already-processed order as an error.

            // Endpoints carrying a token (get-user-info / change-balance) must prove
            // the token really belongs to that uid. The signature only proves the
            // call came from the games platform — the token is what proves the
            // player. A token that doesn't resolve, or resolves to a DIFFERENT uid,
            // is a mismatch → 10003. (make-up-orders has no token, so it is skipped.)
            if ($request->filled('token') && $request->has('uid')) {
                $userId = $this->findUserByToken($request->token);
                if (!$userId || $userId != $request->uid) {
                    return response()->json([
                        'errorCode' => 10003,
                        'errorMsg' => 'token/uid mismatch'
                    ]);
                }
            }


            switch ($path) {
                case 'leader-cc-game/change-balance':
                    $requiredParams = ['orderId', 'gameId', 'roundId', 'uid', 'coin', 'type', 'rewardType', 'token', 'sign'];
                    foreach ($requiredParams as $p) {
                        if (!$request->has($p)) {
                            return response()->json([
                                'errorCode' => 4005,
                                'errorMsg' => 'Missing signature parameters'
                            ]);
                        }
                    }
                    $rawBase =
                        $request->orderId .
                        $request->gameId .
                        $request->roundId .
                        $request->uid .
                        $request->coin .
                        $request->type .
                        $request->rewardType .
                        $request->token .
                        $request->input('winId', "") .
                        $request->roomId;
                    break;

                case 'leader-cc-game/get-user-info':
                    $requiredParams = ['gameId', 'uid', 'token', 'roomId', 'sign'];
                    foreach ($requiredParams as $p) {
                        if (!$request->has($p)) {
                            return response()->json([
                                'errorCode' => 4005,
                                'errorMsg' => 'Missing signature parameters'
                            ]);
                        }
                    }
                    $rawBase =
                        $request->gameId .
                        $request->uid .
                        $request->token .
                        $request->roomId;
                    break;

                case 'leader-cc-game/make-up-orders':
                    $requiredParams = ['orderId', 'gameId', 'roundId', 'uid', 'coin', 'rewardType', 'sign'];
                    foreach ($requiredParams as $p) {
                        if (!$request->has($p)) {
                            return response()->json([
                                'errorCode' => 4005,
                                'errorMsg' => 'Missing signature parameters'
                            ]);
                        }
                    }
                    $rawBase =
                        $request->orderId .
                        $request->gameId .
                        $request->roundId .
                        $request->uid .
                        $request->coin .
                        $request->rewardType .
                        $request->input('winId', '') .
                        $request->input('roomId', '');
                    break;

                default:
                    return response()->json([
                        'errorCode' => 4006,
                        'errorMsg' => 'Endpoint not allowed for this middleware'
                    ]);
            }

            // Accept the request if its signature matches ANY active provider's
            // key. The first match tells us which provider called; stash it for
            // downstream use (per-provider logic can read game_provider_code).
            $suppliedSign = strtolower((string) $request->input('sign'));
            $matchedCode = null;
            foreach ($activeKeys as $code => $key) {
                if (hash_equals(strtolower(md5($rawBase . $key)), $suppliedSign)) {
                    $matchedCode = $code;
                    break;
                }
            }

            if ($matchedCode === null) {
                return response()->json([
                    'errorCode' => 10004,
                    'errorMsg' => 'Verify signature fail'
                ]);
            }

            $request->attributes->set('game_provider_code', $matchedCode);

            $response = $next($request);
        } catch (\Throwable $e) {

            $duration = microtime(true) - $start;
            return response()->json([
                'errorCode' => 5000,
                'errorMsg' => 'Internal server error',
                'details' => $e->getMessage(),
            ]);
        }

        $duration = microtime(true) - $start;


        return $response;
    }

    public function findUserByToken($token): mixed
    {
        $token = urldecode($token);
        if (strpos($token, '|') !== false) {
            [$_, $plainToken] = explode('|', $token, 2);
        } else {
            $plainToken = $token;
        }

        // The game client fires consecutive batches with the SAME token. Cache the
        // token->user resolution briefly so we don't hit personal_access_tokens on
        // every request (cuts a Sanctum query per call). A miss is cached as a
        // sentinel so invalid tokens don't repeatedly query either; key is hashed
        // so the raw token never lands in the cache store.
        $cacheKey = 'leadercc:token:' . hash('sha256', $plainToken);

        $resolved = Cache::remember($cacheKey, 60, function () use ($plainToken) {
            $personalToken = PersonalAccessToken::findToken($plainToken);
            // Sentinel 0 = "no such token" (Cache::remember treats null as a miss
            // and would re-run the resolver every time).
            return $personalToken ? $personalToken->tokenable_id : 0;
        });

        return $resolved === 0 ? null : $resolved;
    }




}
