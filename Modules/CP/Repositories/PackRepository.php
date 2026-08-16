<?php
namespace Modules\CP\Repositories;

use App\Models\Pack;
use Carbon\Carbon;

class PackRepository
{
    public function createPack($data)
    {
        return Pack::create($data);
    }

    public function findByUserIdAndTargetId($userId, $targetId)
    {
        return Pack::where('user_id', $userId)
                    ->where('target_id', $targetId)
/*                     ->where('expire', '<', Carbon::now()->timestamp)
                    ->where('expire', '!=', 0) */
                    ->first();
    }

    public function countUserVipPacks($userId)
    {
        return Pack::query()
            ->where('user_id', $userId)
            ->where('expire', '<', Carbon::now()->timestamp)
            ->where('expire', '!=', 0)
            ->where('type', 100)
            ->count();
    }
}
