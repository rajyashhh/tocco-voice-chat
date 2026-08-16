<?php

namespace App\Services;

use App\Models\Pk;;
use App\Models\User;
use App\Helpers\Common;
use App\Helpers\LogHelper;
use App\helper\RankingHelper;

use Illuminate\Log\LogManager;
use App\Helpers\UserPackHelper;
use App\Helpers\UserLevelHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use Illuminate\Pagination\Paginator;
use App\Http\Resources\TopUserResource;
use App\Repositories\RankingRepository;
use App\Http\Resources\Api\V1\RoomResource;
use App\Http\Resources\GameRankingResource;

use App\Tik\Repositories\GiftLogRepository;
use Modules\CP\Transformers\RankingResource;
use App\Http\Resources\RankingUserV2Resource;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\CP\Transformers\TopRankingResource;
use App\Tik\Repositories\CoinGameUserRepository;
use App\Http\Resources\Api\V1\MangerTypeResource;
use App\Http\Resources\Api\V1\UserRankingCollection;
use App\Http\Resources\Api\V1\UsersRankingCollection;
use App\Http\Resources\RankingGameCollectionResource;
use Modules\Achievement\Http\Services\UserAchievementService;
use Modules\Achievement\Transformers\UserAchievementLevelsResource;
use Modules\CP\Repositories\CpRepository as RepositoriesCpRepository;

class RankingService
{
    protected $rankingRepo, $cpRepository;


    public function __construct(
        RankingRepository $rankingRepo,
        private readonly GiftLogRepository $GiftLogRepository,
        private readonly CoinGameUserRepository $coinGameUserRepository,
        public UserAchievementService $achievementService,
        RepositoriesCpRepository $cpRepository
    ) {
        $this->cpRepository = $cpRepository;
        $this->rankingRepo = $rankingRepo;
    }

    /**
     * Backward-compat mapping for /api/ranking.
     *   class → unified ranking section: 2=wealth 1=charm 3=room 6=games 5=agency 4=lucky
     *   type  → period: 0=hourly 1=daily 2=weekly 3=monthly
     */
    public const CLASS_TO_SECTION = [
        1 => 'charm',
        2 => 'wealth',
        3 => 'room',
        4 => 'lucky',
        5 => 'agency',
        6 => 'games',
    ];

    public const TYPE_TO_PERIOD = [
        0 => 'hourly',
        1 => 'daily',
        2 => 'weekly',
        3 => 'monthly',
    ];

    /**
     * class → [relation used by transformData/prepareResponse to read the ranker].
     * For user-backed sections the member id is a user id (room member = roomowner_id,
     * itself a user id). Agency (5) hydrates an Agency under the `ranker` relation and
     * is handled by its own branch in handleRedisRanking — kept here for completeness.
     */
    private const CLASS_TO_RELATION = [
        1 => 'receiver',
        2 => 'sender',
        3 => 'roomOwner',
        4 => 'user',
        5 => 'ranker',
        6 => 'user',
    ];

    /**
     * Single ranking entry point for /api/ranking. The unified Redis sorted sets
     * (RankingScoreService) are the ONLY source — every class (1,2,3,4,5,6) reads
     * Redis. No feature flag, no legacy cron/cache fallback.
     */
    public function getRanking22(int $class, int $type, $user, int $limit)
    {
        return $this->handleRedisRanking($class, $type, $limit, $user);
    }

    /**
     * Unified Redis read path. Same response contract as before
     * ({user, top, other, others_pagination}) so the app never breaks:
     *   topN (ZREVRANGE) → hydrate (WHERE id IN ≤10, limited eager-load, Redis order)
     *   → transformData → prepareResponse, then the requesting user's own card is
     *   forced from scoreAndRank (ZSCORE + ZREVRANK) so it shows even outside top 10.
     *
     * Agency (class 5) is fully on Redis too: it hydrates Agency rows shaped for
     * NewAgencyRankingResource (->total_gifts + ->ranker(Agency with owner)) and is
     * returned as a bare collection so RankingHelper::transformData wraps it in
     * NewAgencyRankingResource — identical JSON to the old gift_rankings path.
     */
    protected function handleRedisRanking(int $class, int $type, int $limit, $user)
    {
        $section  = self::CLASS_TO_SECTION[$class] ?? 'charm';
        $period   = self::TYPE_TO_PERIOD[$type] ?? 'daily';
        $relation = self::CLASS_TO_RELATION[$class] ?? 'user';

        $scores = app(\App\Services\RankingScoreService::class);

        $topN = $scores->topN($section, $period, $limit);

        // On-read DB fallback, gated strictly to the empty-bucket case. The hourly
        // bucket rolls every hour and is empty until the first write/backfill, which
        // would blank the board for up to 15 min each hour; the fallback fills it
        // from the same DB source the backfill uses (cached). The hot path (bucket
        // populated) stays pure-Redis — zero extra cost.
        $fromFallback = false;
        if (empty($topN)) {
            $topN = $this->rankingRepo->fallbackTopN($section, $period, $limit);
            $fromFallback = !empty($topN);
        }

        if ($class == 5) {
            // Agency: bespoke resource contract (NewAgencyRankingResource).
            return $this->rankingRepo->hydrateAgencyRanking($topN);
        }

        $data = $this->rankingRepo->hydrateRanking($topN, $relation, $class);

        $this->transformData($data, $class, 'user_id', $relation);

        // Authoritative own-card score/rank straight from Redis (always present,
        // even when the user is outside the hydrated top N). The rank is taken from
        // scoreAndRank (ZREVRANK) — NOT from scanning the top-N collection, which
        // only holds the first $limit members and uses the receiver_id key.
        // When the data came from the DB fallback (empty Redis bucket), derive the
        // own card from the fallback rows instead of an empty ZSET.
        if ($fromFallback) {
            [$ownScore, $ownRank] = $this->ownFromFallback($topN, $user->id);
        } else {
            $own = $scores->scoreAndRank($section, $period, $user->id);
            $ownScore = $own['score'];
            $ownRank = $own['rank'];
        }
        $userExp = (object) ['exp' => $ownScore];

        return $this->prepareResponse($data, $user, $type, 'user_id', $user->id, $class, $limit, $userExp, $ownRank);
    }

    protected function roomData($ownerRoom)
    {
        if (!$ownerRoom) return null;
        $data = [];
        $pks = !is_null($ownerRoom?->id) ? $this->getRoomTwoLastPk($ownerRoom->id) : null;
        $data =  [
            "id" => @$ownerRoom->id ?? 0,
            "owner_uuid" => @@$ownerRoom->owner->uuid ?? 0,
            "room_name" => @$ownerRoom->room_name ?? '',
            "room_cover" => @$ownerRoom->room_cover ?? '',
            "room_background" => @$ownerRoom->final_room_image ?? '',
            "mode" => @$ownerRoom->mode ?? 0,
            'giftPrice' => @$ownerRoom->session_string ?? "0",
            "is_pk"               => (@$pks[0]) && @$pks[0]->end_at >= now() ? @$pks[0]->status : 0,
            "show_pk"             => @$ownerRoom->is_show_pk ?? 0,
            'password_status'     => !(@$ownerRoom->room_pass == ""),
            'type-number'                => @$ownerRoom->room_type ?? 0,
            'type' => @$ownerRoom->myType ?: new \stdClass(),

        ];

        return $data;
    }
    private function getRoomTwoLastPk(int $roomId)
    {
        return Pk::query()
            ->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();
    }

    protected function transformData(&$data, $class, $key, $relation)
    {
        $data = $data->reject(function ($q) {
            return $q->exp == 0;
        });

        $data = $data->values()->map(function ($item, $key) use ($data) {
            if ($key === 0) {
                $item->exp_diff = 0;
            } else {
                $item->exp_diff = $data[$key - 1]->exp - $item->exp  + 1;
            }
            return $item;
        });


        $data = $data->map(function ($v) use ($key, $class, $relation) {
            $achievement_images = [];
            $user = $v->$relation;

            if ($user == null) {
                return null;
            }

            $hasColor = Common::hasInPack($user->id, 18, true);

            $color_name = $hasColor ? common::wareUserVip($user->id, 18, 'color') : null;
            $color_name = is_string($color_name) ? $color_name : '';
            if ($user->medals) {
                foreach ($user->medals as $medal) {
                    if ($medal->achievementLevel) {
                        $achievementData = [
                            'image' => @$medal->achievementLevel->valid_image,
                            'title' => @$medal->achievementLevel?->achievement?->name ?? '',
                            'created_at' => @$medal->created_at,
                        ];
                        $achievement_images[] = $achievementData;
                    }
                }
            }

            $v->user_id = $user->id;
            $v->color_name = $color_name;

            $value = $v->exp;
            $v->exp = numToString(ceil((float)$v->exp));
            $v->exp_int = ceil($value);

            $value2 = $v->exp_diff;
            $v->remaining = numToString(ceil($v->exp_diff));
            $v->remaining_int = ceil($value2);

            $v->name = $class == 3 ? (@$user->ownerRoom?->room_name ?? '') : $user->name;
            $v->avatar = $class == 3 ? (@$user->ownerRoom?->room_cover ?? '') : $user->profile?->avatar;
            $v->frame = Common::getUserDress($user->id, $user->dress_1, 4, 'img2', true) ?: Common::getUserDress($user->id, $user->dress_1, 4, 'img1', true);
            $v->frame_id = $user->dress_1;
            $v->type_user =  intval(@$user->type_user) ?: 0;
            $v->manger_type =  !$user->mangerType ? null : new MangerTypeResource(@$user->mangerType);

            $v->vip_level = @$user->UserVip->level ?? 0;
            $v->sender_level = @$user->total_sender_level;
            $v->reciver_level = @$user->total_received_level;

            $total_received_level_img = Common::getImageTotalReceiverOrSender($user->total_received_level);
            $total_sender_level_img = Common::getImageTotalReceiverOrSender($user->total_sender_level);

            $v->vip_level_img = @$user->UserVip?->OVip?->img ?? '';
            $v->sender_level_img = @$total_received_level_img->img ?? '';
            $v->reciver_level_img = @$total_sender_level_img->img ?? '';

            $v->country = @$user->country;
            $v->age = @$user->profile->age ?? 'P';
            $v->achievement_images = $achievement_images;
            $v->room = $class == 3 ? $this->roomData(@$user->ownerRoom) : null;
            unset($v->$relation);
            return $v;
        })->reject(function ($v) {
            return $v == null;
        });
    }

    protected function prepareResponse($data, $user, $type, $key, $userId, $class, $limit, $userExp = null, $authoritativeRank = null)
    {

        $data->each(function ($item) {
            $hasColor = Common::hasInPack($item->user_id, 18, true) ?? '';
            $color = $hasColor ? Common::wareUserVip($item->user_id, 18, 'color') : null;
            $item->color_name = (is_string($color) && $color !== 'NULL') ? $color : '';
        });

        $achievement_images = [];
        if ($user->medals) {
            foreach ($user->medals as $medal) {
                if ($medal->achievementLevel) {
                    $achievementData = [
                        'image' => @$medal->achievementLevel->valid_image,
                        'title' => @$medal->achievementLevel?->achievement?->name ?? '',
                        'created_at' => @$medal->created_at,
                    ];
                    $achievement_images[] = $achievementData;
                }
            }
        }
        $kong['user_id']    = 0;
        $kong['uuid']       = '';
        $kong['exp']        = '0';
        $kong['exp_int']        = 0;
        $kong['remaining']        = '0';
        $kong['remaining_int']        = 0;
        $kong['name']       = '';
        $kong['avatar']     = '';
        $kong['frame']      = '';
        $kong['frame_id']   = 0;
        $kong['sender_img'] = '';
        $kong['reseverimg'] = '';
        $kong['vip_level']  =  0;
        $kong['sender_level'] = 0;
        $kong['reciver_level'] = 0;

        $kong['vip_level_img'] = '';
        $kong['sender_level_img'] = '';
        $kong['reciver_level_img'] = '';
        $kong['age'] = 0;

        $kong['type_user'] = 0;
        $kong['manger_type'] = null;
        $kong['achievement_images'] = [];
        $kong['color_name'] = '';



        $data[0] = isset($data[0]) ? $data[0] : $kong;
        $data[1] = isset($data[1]) ? $data[1] : $kong;
        $data[2] = isset($data[2]) ? $data[2] : $kong;
        //        if ($limit == 3) return $data;


        // Redis path supplies the authoritative ZREVRANK (works even when the user
        // is outside the hydrated top N). The gift_logs room-scoped callers pass
        // null and fall back to scanning the top-N collection by $key.
        $user->sort = $authoritativeRank !== null
            ? (string) $authoritativeRank
            : $this->getUserSortValue($data, $userId, $key);
        $user->user_id = $user->id;

        $arr['user'] = $user->only('user_id', 'uuid', 'exp', 'name', 'avatar', 'frame', 'frame_id', 'manger_type_id', 'age');

        $sender_img = @$user->getImageReceiverOrSender('sender_id', 2)?->img ?? '';
        $total_received_level_img = Common::getImageTotalReceiverOrSender($user->total_received_level);
        $total_sender_level_img = Common::getImageTotalReceiverOrSender($user->total_sender_level);
        $vip_level  = Common::ovip_center_rank($arr['user']['user_id']);
        $vip_level_img  = Common::ovip_center_rank_img($arr['user']['user_id']);
        $hasColor = Common::hasInPack($user->id, 18, true);

        $color_name = $hasColor ? common::wareUserVip($user->id, 18, 'color') : null;
        $color_name = is_string($color_name) ? $color_name : '';

        // $levels =Common::getSenderAndReceiverLevels($user->id);
        if (gettype($vip_level) != 'integer') {
            $vip_level = 0;
        }

        if (is_object($vip_level_img) && get_class($vip_level_img) === 'stdClass') {
            $vip_level_img = 0;
        }

        $userData = $data->where($key, $user->id)->first();

        $arr['user']['exp'] = ($userExp != null) ? (@$userExp->exp ?? '0') : (@$userData->exp ?? '0');
        $arr['user']['sender_img'] = $sender_img;
        $arr['user']['vip_level']  = $vip_level ?? 0;
        $arr['user']['sender_level']  = $user->total_sender_level ?? '';
        $arr['user']['reciver_level']  = $user->total_received_level ?? '';
        $arr['user']['vip_level_img']  = $vip_level_img == 0 ? "" : $vip_level_img;
        $arr['user']['sender_level_img']  = $total_sender_level_img->img ?? '';
        $arr['user']['reciver_level_img']  = $total_received_level_img->img ?? '';
        $arr['user']['type_user'] =  intval(@$user->type_user) ?: 0;
        $arr['user']['country'] =  @$user->country;
        $arr['user']['manger_type'] = !$user->mangerType ? null : new MangerTypeResource(@$user->mangerType);
        $arr['user']['age'] = @$user->profile?->age ?? '';
        $arr['user']['color_name'] = $color_name ?? '';
        $arr['user']['achievement_images'] = $achievement_images;


        $toArray = $data->toArray();
        $countData = count($data);
        $arr['top'] = $countData < 4 ? $data : array_slice($toArray, 0, 3);
        $arr['other'] = $countData < 4 ? [] : array_slice($toArray, 3);
        return $arr;
    }

    protected function getClassKeywordsAndRelation($class)
    {
        if ($class == 1) {
            return ['receiver_id', 'receiver'];
        } elseif ($class == 2) {
            return ['sender_id', 'sender'];
        } elseif ($class == 3) {
             return ['roomowner_id', 'roomOwner'];
           // return ['room_id', 'roomId'];
        } elseif ($class == 5) {
            return ['agency_id', 'agency'];
        } else {
            return ['sender_id', 'sender'];
        }
    }

    /**
     * Own-card score + 1-based rank from a DB-fallback topN (used only when the
     * Redis bucket was empty). The fallback array is already score-desc and sliced
     * to the limit, so a user outside it returns [0.0, null] — same "outside the
     * board" semantics as a Redis ZSCORE miss; prepareResponse renders it as 99+.
     *
     * @param array<int, array{member:string, score:float}> $topN
     * @return array{0: float, 1: ?int}
     */
    private function ownFromFallback(array $topN, int $userId): array
    {
        foreach ($topN as $i => $row) {
            if ((int) $row['member'] === $userId) {
                return [(float) $row['score'], $i + 1];
            }
        }

        return [0.0, null];
    }

    private function getUserSortValue($data, $user_id, $key = 'receiver_id')
    {
        $sort = 0;
        foreach ($data as $i => $v) {
            if (isset($v->$key) && $v->$key == $user_id) {
                $sort = $i + 1;
                break;
            }
        }
        return $sort ? (string) $sort : '99+';
    }

    public function getRankingOneRoom($class, $type, $user, $limit, $room_id, $sent_to_owner)
    {

        [$keywords, $rel] = $this->getClassKeywordsAndRelation($class);

        $data = $this->rankingRepo->getGiftLogsForRoomOwnerId($class, $rel, $type, $limit, $room_id, $keywords);
        $userExp = $this->rankingRepo->getGiftLogsUserForRoomOwnerId($class, $rel, $type, $user->id, $room_id, $keywords);
        $this->transformData($data, $class, $keywords, $rel);
        return $this->prepareResponse($data, $user, $type, $keywords, $user->id, $class, $limit, $userExp);
    }

    /**
     * One-room ranking scoped by gift_logs.room_id (the camelCase `roomId` request
     * branch). Preserves the exact room_id-column semantics the removed
     * removed V2 getRankingOneRoom had; getRankingOneRoom above keeps the
     * roomowner_id-column semantics of the snake `room_id` branch. Same gift_logs
     * source, same transform/response contract.
     */
    public function getRankingOneRoomById($class, $type, $user, $limit, $room_id, $sent_to_owner)
    {

        [$keywords, $rel] = $this->getClassKeywordsAndRelation($class);

        $data = $this->rankingRepo->getGiftLogsForRoomById($class, $rel, $type, $limit, $room_id, $keywords);
        $userExp = $this->rankingRepo->getGiftLogsUserForRoomById($class, $rel, $type, $user->id, $room_id, $keywords);
        $this->transformData($data, $class, $keywords, $rel);
        return $this->prepareResponse($data, $user, $type, $keywords, $user->id, $class, $limit, $userExp);
    }

    public function getTodayTopUsers()
    {
        $data = $this->cpRepository->getCpRankingWithOutRelation(1);
        $cp_top_2 = $data->take(3);
        //dd($cp_top_2 -> toArray());
        $topGamer = $this->coinGameUserRepository->topThree();
        return [
            'sender'    => $this->getRankUserAvatars('wealth'),
            'receiver'  => $this->getRankUserAvatars('charm'),
            'room'      => $this->getRankRoomAvatars('room'),
            'top_cp' => array_values(TopRankingResource::collection($cp_top_2)->toArray(request())),
            'top_gamer' => GameRankingResource::collection($topGamer),
        ];
    }

    /**
     * Home top-avatars from the unified Redis daily sorted sets (single system).
     * sender card = wealth section, receiver card = charm section. Hydrates only
     * the avatar relation, preserving Redis (score-desc) order.
     */
    protected function getRankUserAvatars(string $section, int $limit = 3): array
    {
        $scores = app(\App\Services\RankingScoreService::class);
        $topN = $scores->topN($section, 'daily', $limit);

        return $this->rankingRepo
            ->topRankingAvatars($topN, false)
            ->values()
            ->toArray();
    }

    /**
     * Home top-room covers from the unified Redis daily 'room' sorted set (member =
     * room owner user id). Hydrates ownerRoom.room_cover, preserving Redis order.
     */
    protected function getRankRoomAvatars(string $section, int $limit = 3): array
    {
        $scores = app(\App\Services\RankingScoreService::class);
        $topN = $scores->topN($section, 'daily', $limit);

        return $this->rankingRepo
            ->topRankingAvatars($topN, true)
            ->values()
            ->toArray();
    }
}
