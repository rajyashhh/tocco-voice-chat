<?php

namespace App\Http\Controllers\Api\V1;

use App\helper\TryCatchHelper;
use App\Helpers\Common;
use App\helper\RankingHelper;
use App\Services\UserService;
use Illuminate\Http\Request;
use App\Services\RankingService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class RankingController extends Controller
{

    protected $rankingService;
    protected $userService;

    public function __construct(RankingService $rankingService, UserService $userService)
    {
        $this->rankingService = $rankingService;
        $this->userService = $userService;

    }

    public function ranking2(Request $request)
    {

        // return Common::apiResponse(0, __('closed for update'));

        // throw new Exception(__('closed for update'));

        return TryCatchHelper::handle(function () use ($request) {

            $class = (int) ($request->class ?? 1);
            $type  = (int) ($request->type ?? 1);

            if ($error = RankingHelper::validateParams($class, $type)) {
                return $error;
            }

            $limit = RankingHelper::getLimit((bool) $request->is_home);

            $data = $this->rankingService->getRanking22($class, $type, $request->user(), $limit);

            $data = RankingHelper::transformData($class, $data);

            return $data;
        });
    }

    public function topUserRanking()
    {
        // Stampede-protected cache + stale-while-revalidate.
        // Under load across 6 replicas, a plain Cache::remember lets every request that hits an
        // expired key run the heavy aggregation simultaneously (cache stampede), saturating the DB
        // and producing 504s. Here only ONE request rebuilds (guarded by Cache::lock); the rest
        // keep serving the last known value, so the DB sees at most one rebuild query.
        $cacheKey   = 'top_user_ranking';
        $freshKey   = 'top_user_ranking:fresh';
        $freshTtl   = 300;  // value is considered fresh for 5 minutes
        $staleTtl   = 1800; // keep the stale value up to 30 min as a fallback while rebuilding

        $cached = Cache::get($cacheKey);

        // Fresh hit: serve immediately.
        if ($cached !== null && Cache::get($freshKey) !== null) {
            return Common::apiResponse(true, 'Success', $cached);
        }

        // Stale or missing: a single request rebuilds, others serve the stale value.
        $lock = Cache::lock('top_user_ranking:lock', 30);

        if ($lock->get()) {
            try {
                $todayTopUsers = $this->rankingService->getTodayTopUsers();
                Cache::put($cacheKey, $todayTopUsers, $staleTtl);
                Cache::put($freshKey, true, $freshTtl);
                $cached = $todayTopUsers;
            } finally {
                $lock->release();
            }
        } elseif ($cached === null) {
            // No stale value to fall back on (cold start) and another worker holds the lock:
            // wait briefly for it to finish rather than hammering the DB.
            $lock->block(5, function () use (&$cached, $cacheKey, $freshKey, $freshTtl, $staleTtl) {
                $cached = Cache::get($cacheKey);
                if ($cached === null) {
                    $cached = $this->rankingService->getTodayTopUsers();
                    Cache::put($cacheKey, $cached, $staleTtl);
                    Cache::put($freshKey, true, $freshTtl);
                }
            });
        }

        return Common::apiResponse(true, 'Success', $cached);
    }

    public function oneRoomRanking(Request $request)
    {
        $class = $request->class ?: 1;
        $type = $request->type !== null ? $request->type : 1;

        if (!in_array($class, [1, 2,]) || !in_array($type, [0, 1, 2, 3, 4])) {
            return Common::apiResponse(0, 'Parameter error', null, 422);
        }

        if (!$request->room_id && !$request->roomId) {
            return Common::apiResponse(0, 'Parameter error', null, 422);
        }

        $limit = $request->is_home ? 3 : 20;

        if ($request->roomId) {
            $data = $this->rankingService->getRankingOneRoomById($class, $type, $request->user(), $limit, $request->roomId, $request->sent_to_owner);
        }

        if ($request->room_id ) {
            $data = $this->rankingService->getRankingOneRoom($class, $type, $request->user(), $limit, $request->room_id, $request->sent_to_owner );
        }
        return Common::apiResponse(1, '', $data);
    }
}
