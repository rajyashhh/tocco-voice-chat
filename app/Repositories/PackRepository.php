<?php

namespace App\Repositories;

use App\Models\Pack;
use Illuminate\Support\Facades\DB;
use App\Tik\Repositories\PackRepository as Repository;

class PackRepository extends Repository
{
    public function getExistingPack($userId, $type, $targetId)
    {
        return DB::table('packs')->where(['user_id' => $userId, 'type' => $type, 'target_id' => $targetId])->value('id');
    }

    public function createPack(array $data)
    {
        Pack::query()->create($data);
    }

    public function getTargetIdsByUserAndType($userId, array $types)
    {
        return DB::table('packs')->where(['user_id' => $userId])->whereIn('type', $types)->pluck('target_id')->toArray();
    }

    public function getWaresByConditions($vipLevel, array $types, array $ids)
    {
        return DB::table('wares')
            ->where(['get_type' => 1, 'enable' => 1])
            ->where('level', '<=', $vipLevel)
            ->whereIn('type', $types)
            ->whereNotIn('id', $ids)
            ->selectRaw('id,type,expire')
            ->get();
    }

    public function deleteExpiredPacks($userId)
    {
        return Pack::where('user_id', $userId)
            ->where('expire', '<=', time())
            ->where('expire', '!=', 0)
            ->delete();
    }
    public function deleteAllExpiredPacks()
    {
        return Pack::where('expire', '<=', time())->where('expire', '!=', 0)->delete();
    }
}
