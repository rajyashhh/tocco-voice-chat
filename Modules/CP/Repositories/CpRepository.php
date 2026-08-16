<?php

namespace Modules\CP\Repositories;

use App\Models\GiftLog;
use App\Models\Ware;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\CP\Entities\Cp;
use Modules\CP\Entities\CpRelation;
use Modules\CP\Entities\UserRelationAvilable;
use Modules\CP\Enums\CpStatus;


class CpRepository
{
    public function getCpRelationById($id)
    {
        return CpRelation::find($id);
    }

    public function findStoppedRelationBetweenTwoUsers($userOne, $userTwo, $type)
    {
        return Cp::where(function ($q) use ($userOne, $userTwo) {
            $q->where(function ($q) use ($userOne, $userTwo) {
                $q->where('user_one_id', $userOne)->where('user_two_id', $userTwo);
            })
                ->orWhere(function ($q) use ($userOne, $userTwo) {
                    $q->where('user_one_id', $userTwo)->where('user_two_id', $userOne);
                });
        })
            ->whereHas('cpRelation', function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->where("status", CpStatus::STOPED)
            ->first();
    }

    public function findCpBetweenUsers($userOne, $userTwo, $type = null)
    {
        return Cp::lockForUpdate()->where(function ($query) use ($userOne, $userTwo) {
            $query->where(function ($q) use ($userOne, $userTwo) {
                $q->where('user_one_id', $userOne)
                    ->where('user_two_id', $userTwo);
            })->orWhere(function ($q) use ($userOne, $userTwo) {
                $q->where('user_one_id', $userTwo)
                    ->where('user_two_id', $userOne);
            });
        })
            ->whereHas('cpRelation', function ($q) use ($type) {
                if ($type) {
                    $q->where('type', $type);
                } else {
                    $q->where('type', '!=', 'solution');
                }
            })
            ->whereIn('status', [CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->first();
    }

    public function getCpCount($userId)
    {
        return Cp::where(function ($query) use ($userId) {
            $query->where("user_one_id", $userId)
                ->orWhere("user_two_id", $userId);
        })
            ->whereHas("cpRelation", function ($q) {
                $q->where('type', '!=', 'solution');
            })
            ->whereIn("status", [0, 1, 4])
            ->count();
    }

    public function checkExistingCp($userId, $otherUserId)
    {
        return Cp::where(function ($query) use ($userId, $otherUserId) {
            $query->where("user_one_id", $userId)
                ->where("user_two_id", $otherUserId)
                ->orWhere(function ($query) use ($userId, $otherUserId) {
                    $query->where("user_two_id", $userId)
                        ->where("user_one_id", $otherUserId);
                });
        })->whereHas("cpRelation", function ($q) {
            $q->where('type', '!=', 'solution');
        })
            ->whereIn("status", [CpStatus::PENDING->value, CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->first();
    }

    public function checkExistingSecondUserCp($otherUserId, $relationId)
    {
        return Cp::where(function ($query) use ($otherUserId) {
            $query->where("user_two_id", $otherUserId)->orWhere("user_one_id", $otherUserId);
        })->where('cp_relation_id', $relationId)->whereIn("status", [CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->first();
    }

    public function checkExistingFirstUserCp($otherUserId)
    {
        return Cp::where(function ($query) use ($otherUserId) {
            $query->where("user_two_id", $otherUserId)->orWhere("user_one_id", $otherUserId);
        })->whereHas("cpRelation", function ($q) {
            $q->where('type', '!=', 'solution');
        })->whereIn("status", [CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->first();
    }

    public function checkExistingCpLovlyForUser($userId)
    {
        return Cp::where(function ($query) use ($userId) {
            $query->where("user_one_id", $userId)
                ->orWhere("user_two_id", $userId);
        })
            ->whereHas('relation', function ($q) {
                $q->where('relations_number', 1);
            })
            ->whereIn("status", [CpStatus::PENDING->value, CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->first();
    }

    public function checkExistingCpLovly($userId, $otherUserId)
    {
        return Cp::where(function ($query) use ($userId, $otherUserId) {
            $query->where("user_one_id", $userId)
                ->where("user_two_id", $otherUserId)
                ->orWhere(function ($query) use ($userId, $otherUserId) {
                    $query->where("user_two_id", $userId)
                        ->where("user_one_id", $otherUserId);
                });
        })->relation()
            /// TODO convert these status to enum
            ->whereIn("status", [CpStatus::PENDING->value, CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            // ->where("cp_relation_id",5)
            ->first();
    }

    public function countExistingCpSameRelation($userId, $relationId)
    {
        return Cp::where(function ($query) use ($userId,) {
            $query->where(function ($query) use ($userId,) {
                $query->where("user_two_id", $userId)->orWhere("user_one_id", $userId);
            });
        })->where('cp_relation_id', $relationId)
            /// TODO convert these status to enum
            ->whereIn("status", [CpStatus::PENDING->value, CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->count();
    }

    public function countExistingCpSameRelationActive($userId, $relationId)
    {
        return Cp::where(function ($query) use ($userId,) {
            $query->where(function ($query) use ($userId,) {
                $query->where("user_two_id", $userId)->orWhere("user_one_id", $userId);
            });
        })->where('cp_relation_id', $relationId)
            /// TODO convert these status to enum
            ->whereIn("status", [CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
            ->count();
    }

    public function checkExistingCpOne($userId, $cpId)
    {
        return Cp::where("cp_relation_id", $cpId)->where(function ($query) use ($userId) {
            $query->where('user_one_id', $userId)
                ->orWhere('user_two_id', $userId);
        })->whereIn("status", [CpStatus::ACTIVE->value, CpStatus::RESTORED->value])->first();
    }
    public function checkExistingCpSendingOne($userId, $cpId)
    {
        return Cp::where("cp_relation_id", $cpId)->where(function ($query) use ($userId) {
            $query->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
        })->whereIn("status", [CpStatus::PENDING->value, CpStatus::RESTORED->value])->first();
    }

    public function checkExistingCpSendingTwo($userId, $cpId)
    {
        return Cp::where("cp_relation_id", $cpId)->where(function ($query) use ($userId) {
            $query->where('user_two_id', $userId)->orWhere('user_one_id', $userId);
        })->whereIn("status", [CpStatus::PENDING->value, CpStatus::RESTORED->value])->first();
    }


    public function getUserRelationAvailable($userId, $cpRelationId)
    {
        return UserRelationAvilable::where(["user_id" => $userId, "cp_relation_id" => $cpRelationId])
            ->where("count", ">", 0)
            ->first();
    }

    public function decrementUserRelationCount($relation)
    {
        $relation->count -= 1;
        $relation->save();
    }

    public function createCp($data)
    {
        return Cp::create($data);
    }

    public function getRequestsForUser($userId)
    {
        return Cp::where("user_two_id", $userId)
            ->where("status", 0)
            ->get();
    }

    public function findCpById($cpId)
    {
        return Cp::find($cpId);
    }

    public function updateCpStatus(Cp $cp, $status)
    {
        $cp->status = $status;
        return $cp->save();
    }

    public function updateOrCreateUserRelation($userId, $cpRelationId)
    {
        return UserRelationAvilable::updateOrCreate(
            [
                "user_id" => $userId,
                "cp_relation_id" => $cpRelationId,
            ],
            [
                "count" => DB::raw('count + 1'),
            ]
        );
    }

    public function getCpRanking0($relationType, $type)
    {
        return GiftLog::selectRaw('cp_id, SUM(giftNum * giftPrice) as total_gifts')
            ->whereNotNull("cp_id")
            ->whereHas('cp.relation', function ($q) use ($relationType) {
                $q->where('type', $relationType);
            })
            ->with([
                'cp' => function ($query) {
                    $query->select('id', 'di', 'level_id', 'user_one_id', 'user_two_id', 'cp_relation_id');
                },
                'cp.relation'
            ])
            ->when($type, function ($query) use ($type) {
                switch ($type) {
                    case 1:
                        return $query->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()]);
                    case 2:
                        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    case 3:
                        return $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                }
            })
            ->groupBy('cp_id')
            ->orderByDesc('total_gifts')
            ->take(20)
            ->get();
    }





    // public function getCpRanking($relationType, $type)
    // {
    //     $t = is_numeric($type) ? (int) $type : null;
    //     $timezone = getTimezone();
    //     $now = \Carbon\Carbon::now($timezone);

    //     return GiftLog::query()
    //         ->selectRaw('
    //         cps.id as cp_id,
    //         cps.di,
    //         cps.level_id,
    //         cps.user_one_id,
    //         cps.user_two_id,
    //         cps.cp_relation_id,
    //         cp_relations.type as relation_type,
    //         SUM(gift_logs.giftNum * gift_logs.giftPrice) as total_gifts
    //     ')
    //         ->join('cps', 'cps.id', '=', 'gift_logs.cp_id')
    //         ->join('cp_relations', 'cp_relations.id', '=', 'cps.cp_relation_id')
    //         ->whereNotNull('gift_logs.cp_id')
    //         ->where('cp_relations.type', $relationType)

    //         // Today
    //         ->when(
    //             $t === 1,
    //             function ($q) use ($now) {
    //                 // debugging stops here
    //                 return $q->whereBetween('gift_logs.created_at', [
    //                     $now->copy()->startOfDay()->toDateTimeString(),
    //                     $now->copy()->endOfDay()->toDateTimeString(),
    //                 ]);
    //             }
    //         )

    //         // This week (Saturday–Friday in your code)
    //         ->when(
    //             $t === 2,
    //             fn($q) =>
    //             $q->whereBetween('gift_logs.created_at', [
    //                 $now->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->startOfDay()->toDateTimeString(),
    //                 $now->copy()->startOfWeek(\Carbon\Carbon::SATURDAY)->addDays(6)->endOfDay()->toDateTimeString(),
    //             ])
    //         )

    //         // This month
    //         ->when(
    //             $t === 3,
    //             fn($q) =>
    //             $q->whereBetween('gift_logs.created_at', [
    //                 $now->copy()->startOfMonth()->startOfDay()->toDateTimeString(),
    //                 $now->copy()->endOfMonth()->endOfDay()->toDateTimeString(),
    //             ])
    //         )

    //         ->groupBy(
    //             'cps.id',
    //             'cps.di',
    //             'cps.level_id',
    //             'cps.user_one_id',
    //             'cps.user_two_id',
    //             'cps.cp_relation_id',
    //             'cp_relations.type'
    //         )
    //         ->orderByDesc('total_gifts')
    //         ->limit(20)
    //         ->get()
    //         ->map(function ($row) {
    //             $cp = \App\Models\Cp::with(['level', 'fromUser.profile', 'toUser.profile'])->find($row->cp_id);
    //             if ($cp) $cp->total_gifts = $row->total_gifts;
    //             return $cp;
    //         });
    // }

    public function getCpRanking($relationType, $type)
    {
        $t = is_numeric($type) ? (int) $type : null;
        $timezone = getTimezone();
        $now = \Carbon\Carbon::now($timezone);

        $query = GiftLog::query()
            ->selectRaw('
            cps.id,
            cps.di,
            cps.level_id,
            cps.user_one_id,
            cps.user_two_id,
            cps.cp_relation_id,
            cp_relations.type as relation_type,
            SUM(gift_logs.giftPrice) as total_gifts
        ')
            ->join('cps', 'cps.id', '=', 'gift_logs.cp_id')
            ->join('cp_relations', 'cp_relations.id', '=', 'cps.cp_relation_id')
            ->whereNotNull('gift_logs.cp_id')
            ->where('cp_relations.type', $relationType);

        // Apply date filter
        $query->when(
            $t === 1,
            fn($q) =>
            $q->whereBetween('gift_logs.created_at', [
                $now->copy()->startOfDay()->toDateTimeString(),
                $now->copy()->endOfDay()->toDateTimeString(),
            ])
        );

        $query->when(
            $t === 2,
            fn($q) =>
            $q->whereBetween('gift_logs.created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->startOfWeek()->addDays(6)->endOfDay(),
            ])
        );

        $query->when(
            $t === 3,
            fn($q) =>
            $q->whereBetween('gift_logs.created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])
        );

        $rows = $query
            ->groupBy(
                'cps.id',
                'cps.di',
                'cps.level_id',
                'cps.user_one_id',
                'cps.user_two_id',
                'cps.cp_relation_id',
                'cp_relations.type'
            )
            ->orderByDesc('total_gifts')
            ->limit(20)
            ->get();

        // preload CPs with relations in one query
        $cpIds = $rows->pluck('id');
        $cps = \App\Models\Cp::with(['level:id,img', 'fromUser.profile:id,user_id,avatar,gender', 'toUser.profile:id,user_id,avatar,gender', 'fromUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value'), 'toUser.packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')])
            ->whereIn('id', $cpIds)
            ->get()
            ->keyBy('id');

        // merge totals into the Cp models
        return $rows->map(function ($row) use ($cps) {
            if ($cps->has($row->id)) {
                $cp = $cps[$row->id];
                $cp->total_gifts = (int) $row->total_gifts;
                return $cp;
            }
            return null;
        })->filter();
    }



    public function getCpRankingWithOutRelation(int $type)
    {
        $query = GiftLog::selectRaw('cp_id, SUM(giftNum * giftPrice) as total_gifts')
            ->whereNotNull('cp_id')
            ->join('cps', 'gift_logs.cp_id', '=', 'cps.id')
            ->join('cp_relations', 'cps.cp_relation_id', '=', 'cp_relations.id')
            ->whereNotNull('cp_relations.type')->where('cp_relations.type', 'lovely');

        // Apply date filters
        $query->when($type, function ($query) use ($type) {
            switch ($type) {
                case 1:
                    $query->whereBetween('gift_logs.created_at', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
                    break;
                case 2:
                    $query->whereBetween('gift_logs.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;
                case 3:
                    $query->whereMonth('gift_logs.created_at', Carbon::now()->month)
                        ->whereYear('gift_logs.created_at', Carbon::now()->year);
                    break;
            }
        });

        // Group and rank directly in SQL
        $result = $query->groupBy('cp_id', 'cp_relations.type')
            ->orderByDesc('total_gifts')
            ->with(['cp' => function ($q) {
                $q->select('id', 'di', 'level_id', 'user_one_id', 'user_two_id', 'cp_relation_id')
                    ->with(['relation:id,type']);
            }])
            ->get();
        // ->groupBy('cp.relation.type') // just grouping final small set
        // ->map(fn($group) => $group->first()); // pick top 1 per type

        return $result;
    }


    public function getCpList($userId, $activeOnly = false)
    {
        $var = $activeOnly ? [1, 4] : [0, 1, 4];

        return Cp::where(function ($query) use ($userId) {
            $query->where("user_one_id", $userId)
                ->orWhere(function ($query) use ($userId) {
                    $query->where("user_two_id", $userId);
                });
        })
            ->whereIn("status", $var)
            ->get();
    }

    public function cpUserList($userId, $activeOnly = false)
    {
        $var =  [1, 4];

        return Cp::where(function ($query) use ($userId) {
            $query->where("user_one_id", $userId)
                ->orWhere(function ($query) use ($userId) {
                    $query->where("user_two_id", $userId);
                });
        })
            ->whereIn("status", $var)
            ->get();
    }



    public function findWare($wareId)
    {
        return Ware::find($wareId);
    }

    public function getUserCpProfiles($userId, $statuses, $count = 9)
    {
        return Cp::with([
            'cpRelation:id,title,type',
            'toUser:id,name,uuid,special_id,dress_1,dress_2,dress_3',
            'fromUser:id,name,uuid,special_id,dress_1,dress_2,dress_3',
        ])
            ->whereHas("cpRelation", function ($q) {
                $q->where('type', "!=", 'solution');
            })
            ->where(function ($query) use ($userId) {
                $query->where('user_one_id', $userId)
                    ->orWhere('user_two_id', $userId);
            })
            ->whereIn('status', $statuses)
            ->orderByDesc('di')
            ->take($count)
            ->get();
    }


    public function getByUser($userId)
    {
        return Cp::where(function ($query) use ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where("user_one_id", $userId)->whereHas('toUser');
            })->orWhere(function ($q) use ($userId) {
                $q->where("user_two_id", $userId)->whereHas('fromUser');
            });
        })->with('relation:id,title,type', 'toUser', 'fromUser')->get();
    }

    public function getCpsByUserAndRelation(int $userId, int $relationId)
    {
        return Cp::where(function ($q) use ($userId) {
            $q->where('user_one_id', $userId)
                ->orWhere('user_two_id', $userId);
        })
            ->where('cp_relation_id', $relationId)
            ->get();
    }
}
