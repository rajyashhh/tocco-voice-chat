<?php

namespace App\Repositories\Community;

use App\Http\Resources\Api\V1\CommunityResource;
use App\Models\OfficialMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SearchRepository implements SearchRepositoryInterface
{
    public function saveSearchHistory(int $userId, string $keywords): void
    {
        // search column is varchar(255); spammers send very long strings → 1406 truncation error
        $keywords = mb_substr($keywords, 0, 255);

        $searchExists = DB::table('search_histories')
            ->where('user_id', $userId)
            ->where('search', $keywords)
            ->where('type', 2)
            ->exists();

        if (!$searchExists) {
            DB::table('search_histories')->insert([
                'search' => $keywords,
                'user_id' => $userId,
            ]);
        }
    }

    public function searchRooms(int $userId, string $keywords, int $page = 1): array|Collection
    {
        // $user = User::searchByUuid($keywords)->first();
        $user = Auth::user();
        $blockedByMe = $user->blockedUsers()->pluck('from_uid')->toArray();
        $blockedMe = $user->blockedMe()->pluck('user_id')->toArray();

        $blockedUserIds = array_unique(array_merge($blockedByMe, $blockedMe));

        // $user = User::likeSearchByUuid($keywords)
        //     ->with(['packs' => function ($q) {
        //         $q->where('type', 16)
        //             ->where('is_used', 1)
        //             ->where(function ($q) {
        //                 $q->where('expire', 0)
        //                 ->orWhere('expire', '>=', now()->timestamp);
        //             });
        //     }])->first();
        $user = User::query()
            ->fitterByUuid($keywords)
            // ->where(function ($q) use ($keywords) {
            //     $q->where('uuid', 'like', "%{$keywords}%")
            //     ->orWhere('special_id', 'like', "%{$keywords}%");
            // })
            ->with(['packs' => function ($q) {
                $q->where('type', 16)
                    ->where('is_used', 1)
                    ->where(function ($q) {
                        $q->where('expire', 0)
                            ->orWhere('expire', '>=', now()->timestamp);
                    });
            }])
            ->addSelect('*')
            ->selectRaw("((LENGTH(uuid) - LENGTH(REPLACE(uuid, ?, ''))) / CHAR_LENGTH(uuid)) * 100 AS matching_percentage", [$keywords])
            ->orderByDesc('matching_percentage')
            ->first();

        if (!$user || $user?->packs->isNotEmpty()) {
            return [];
        }


        $keywords = $user->id;

        $query = Room::with([
            'owner',
            'owner.packs'
        ])
            ->whereHas('owner', function ($query) {
                $query->where('status', 1);
            })
            ->where(function ($query) use ($keywords, $blockedUserIds) {
                $query->where('uid', 'like', $keywords . '%')
                    ->whereNotIn('uid', $blockedUserIds);
            })
            ->where(function ($query) {
                $query->where('type', '!=', 'live')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'live')
                            ->where('room_status', 1)
                            ->where('is_live', 1);
                    });
            })
            ->orderBy('hot', 'desc')
            ->take(2);


        $rooms = $query->get();


        return $rooms;
    }

    public function searchRoomsV2(int $userId, string $keywords, array $blockedUserIds, int $page = 1): array|Collection
    {
        $user = User::query()
            ->fitterByUuid($keywords)
            ->with(['packs' => function ($q) {
                $q->where('type', 16)
                    ->where('is_used', 1)
                    ->where(function ($q) {
                        $q->where('expire', 0)
                            ->orWhere('expire', '>=', now()->timestamp);
                    });
            }])
            ->addSelect('*')
            ->selectRaw("((LENGTH(uuid) - LENGTH(REPLACE(uuid, ?, ''))) / CHAR_LENGTH(uuid)) * 100 AS matching_percentage", [$keywords])
            ->orderByDesc('matching_percentage')
            ->first();

        if (!$user || $user?->packs->isNotEmpty()) {
            return [];
        }

        $keywords = $user->id;

        return Room::with([
            'owner',
            'owner.packs'
        ])
            ->whereHas('owner', function ($query) {
                $query->where('status', 1);
            })
            ->where(function ($query) use ($keywords, $blockedUserIds) {
                $query->where('uid', 'like', $keywords . '%')
                    ->whereNotIn('uid', $blockedUserIds);
            })
            ->where(function ($query) {
                $query->where('type', '!=', 'live')
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('type', 'live')
                            ->whereHas('owner.roomVisitors');
                    });
            })
            ->orderBy('hot', 'desc')
            ->take(2)
            ->get();
    }

    public function userSearchHand(int $userId, string $keywords, int $page = 1)
    {
        if (!$userId || !$keywords) {
            return [];
        }

        $whereOr = ['uuid' => $keywords];

        $user = Auth::user();
        $blockedByMe = $user->blockedUsers()->pluck('from_uid')->toArray();
        $blockedMe = $user->blockedMe()->pluck('user_id')->toArray();

        $blockedUserIds = array_unique(array_merge($blockedByMe, $blockedMe));

        // $users = User::query()
        //     ->select(['*', DB::raw("((LENGTH(users.uuid) - LENGTH(REPLACE(users.uuid, '{$keywords}', ''))) / CHAR_LENGTH(users.uuid)) * 100 AS matching_percentage")])
        //     ->where(function ($query) use ($keywords) {
        //         $query->where('uuid', 'like', '%' . $keywords . '%')
        //               ->orWhere('special_id', 'like', '%' . $keywords . '%');
        //     })
        //     ->whereNotIn('id', $blockedUserIds)
        //     ->where('status', 1)
        //     ->with(['followedByAuthUser' , 'country']  )
        //     ->orWhere(function ($query) use ($whereOr) {
        //         $query->where($whereOr);
        //     })
        //     ->orderBy('matching_percentage', 'desc')
        //     ->paginate();
        $users = User::query()
            ->select('*')
            ->selectRaw("
            CASE
                WHEN special_id = ? THEN 1000
                WHEN special_id LIKE ? THEN 900 - LENGTH(special_id)
                WHEN uuid = ? THEN 800
                WHEN uuid LIKE ? THEN 700 - LENGTH(uuid)
                WHEN special_id LIKE ? THEN 600
                WHEN uuid LIKE ? THEN 500
                ELSE 0
            END AS total_score
        ", [$keywords, "{$keywords}%", $keywords, "{$keywords}%", "%{$keywords}%", "%{$keywords}%"])
            ->where(function ($query) use ($keywords) {
                $query->where('special_id', 'like', "%{$keywords}%")
                    ->orWhere('uuid', 'like', "%{$keywords}%");
            })
            ->whereNotIn('id', $blockedUserIds)
            ->where('status', 1)
            ->having('total_score', '>', 0)
            ->with([
                'followedByAuthUser', 'country', 'profile', 'mangerType',
                'specialId.ware', 'color_image',
                'receiverLevel:id,img,level', 'senderLevel:id,img,level',
                'agency' => fn($q) => $q->select('id', 'name', 'app_owner_id')->with('owner:id,name'),
            ])
            ->orderByDesc('total_score')
            ->paginate(10, ['*'], 'page', $page);



        return $users;
    }

    public function userSearchHandV2(int $userId, string $keywords, array $blockedUserIds, int $page = 1)
    {
        if (!$userId || !$keywords) {
            return [];
        }

        $users = User::query()
            ->select('*')
            ->selectRaw("
            CASE
                WHEN special_id = ? THEN 1000
                WHEN special_id LIKE ? THEN 900 - LENGTH(special_id)
                WHEN uuid = ? THEN 800
                WHEN uuid LIKE ? THEN 700 - LENGTH(uuid)
                WHEN special_id LIKE ? THEN 600
                WHEN uuid LIKE ? THEN 500
                ELSE 0
            END AS total_score
        ", [$keywords, "{$keywords}%", $keywords, "{$keywords}%", "%{$keywords}%", "%{$keywords}%"])
            ->with([
                'profile:id,user_id,avatar',
                'color_image',
                'packs',
                'eligiblePacks.ware',
                'specialId.ware',
                'ownAgency'
            ])
            ->where(function ($query) use ($keywords) {
                $query->where('special_id', 'like', "%{$keywords}%")
                    ->orWhere('uuid', 'like', "%{$keywords}%");
            })
            ->whereNotIn('id', $blockedUserIds)
            ->where('status', 1)
            ->having('total_score', '>', 0)
            ->orderByDesc('total_score')
            ->take(10)
            ->get();
        //            ->paginate(10, ['*'], 'page', $page);

        return $users;
    }

    public function getUserFriends(int $userId, string $keywords = null, int $perPage = 10, int $currentPage = 1): \Illuminate\Pagination\LengthAwarePaginator
    {

        $usersQuery = User::query()
            ->whereHas('followers', fn($q) => $q->where('user_id', $userId))
            ->whereHas('followeds', fn($q) => $q->where('followed_user_id', $userId));

        if ($keywords) {
            $usersQuery->where(function ($q) use ($keywords) {
                $q->where('name', 'like', '%' . $keywords . '%')
                    ->orWhere('uuid', 'like', '%' . $keywords . '%')
                    ->orWhere('id', 'like', '%' . $keywords . '%');
            });
        }

        return $usersQuery->with([
            'packs' => function ($query) {
                $query->whereIn('type', [4, 18]);
            },
            'chatRoomsAsUser' => function ($q) use ($userId) {
                $q->where('user_id2', $userId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($userId) {
                        $query->where('user_id', '<>', $userId)
                            ->where('status', '<>', 'seen');
                    }]);
            },
            'chatRoomsAsUser2' => function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->withCount(['messages as unread_messages_count' => function ($query) use ($userId) {
                        $query->where('user_id', '<>', $userId)
                            ->where('status', '<>', 'seen');
                    }]);
            },
            'profile', 'UserVip', 'mangerType', 'specialId.ware',
            'country', 'color_image', 'receiverLevel', 'senderLevel',
            'agency.owner',
        ])
            ->paginate($perPage, ['*'], 'page', $currentPage);
    }

    public function getSearchList(int $userId): array
    {
        $hot = DB::table('search_histories')
            ->select(['id', 'search'])
            ->where('type', 1)
            ->orderBy('sort', 'desc')
            ->get();

        $history = DB::table('search_histories')
            ->select(['id', 'search'])
            ->where('type', 2)
            ->where('user_id', $userId)
            ->get();

        return [
            'hot' => $hot,
            'history' => $history,
        ];
    }

    public function clearUserSearchHistory(int $userId): bool
    {
        return DB::table('search_histories')
            ->where('type', 2)
            ->where('user_id', $userId)
            ->delete();
    }

    public function getOfficialMessages(int $userId, int $page = 1): array
    {
        $sys = OfficialMessage::query()
            ->whereIn('user_id', [0, $userId])
            ->where('type', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        $official = OfficialMessage::query()
            ->where('type', 2)
            ->where(function ($query) use ($userId) {
                $query->whereHas('userOfficialMessages', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                    ->orWhere(function ($q) use ($userId) {
                        $q->whereIn('user_id', [0, $userId])
                            ->whereDoesntHave('userOfficialMessages');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $agency = OfficialMessage::query()
            ->whereIn('user_id', [0, $userId])
            ->where('type', 0)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'sys' => CommunityResource::collection($sys),
            'official' => CommunityResource::collection($official),
            'agency' => CommunityResource::collection($agency),
        ];
    }

    public function getNotifications(int $userId, int $type): AnonymousResourceCollection
    {
        $messages = OfficialMessage::query()

            ->when($type == 2, function ($q) use ($userId) {
                $q->where('type', 2)
                    ->where(function ($query) use ($userId) {
                        $query->whereHas('userOfficialMessages', function ($q) use ($userId) {
                            $q->where('user_id', $userId);
                        })
                            ->orWhere(function ($q) use ($userId) {
                                $q->whereIn('user_id', [0, $userId])->whereNull('feature');
                            });
                    });
            })
            ->when($type != 2, function ($q) use ($userId, $type) {
                $q->whereIn('user_id', [0, $userId])
                    ->where('type', $type);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return CommunityResource::collection($messages);
    }
}
