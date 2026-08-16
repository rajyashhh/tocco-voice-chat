<?php

namespace App\Tik\Repositories;

use App\Models\Pack;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;


/**
 * @property  Pack $model
 */
class PackRepository extends AbstractRepository
{


    public function __construct()
    {
        parent::__construct(new Pack());
    }


    public function findByUserId($userId, $type)
    {
        return $this->model->query()
            ->where('type', $type)
            ->where(function ($q) {
                $q->where('expire', '>=', time())->orWhere('expire', '=', 0);
            })
            ->where('user_id', $userId)
            ->where('use_num', '>', 0)
            ->orderByDesc('id')
            ->first();
    }

    public function checkPack($userId, $type, $isAvailable)
    {
        return $this->model->query()->where('user_id', $userId)->where('type', $type)->where('is_used', !$isAvailable)->exists();
    }

    public function changeAvailabilityAllPack($userId, $type, $isAvailable)
    {
        $this->model->query()->where('user_id', $userId)->where('type', $type)->update(['is_used' => $isAvailable]);
        return true;
    }
    public function userPack($userId, $wareId)
    {
        return $this->model->query()->where('user_id', $userId)->where('target_id', $wareId)->first();
    }

    public function updateExpire($pack, $expire)
    {
        $pack->increment('expire', $expire);
        return true;
    }

    public function updatePrice($pack, $price)
    {
        $pack->increment('price', $price);
        return true;
    }

    public function updatePriceWithExpire($pack, $expire, $price)
    {
        $this->updateExpire($pack, $expire);
        $this->updatePrice($pack, $price);
        $this->updatePacks($pack);
        return true;
    }

    public function updatePacks($pack)
    {
        $pack->save();
        return true;
    }

    public function delete($pack)
    {
        $pack->delete();
        return true;
    }

    public function getExistingPack($userId, $type, $targetId)
    {
        return $this->model->where(['user_id' => $userId, 'type' => $type, 'target_id' => $targetId])->value('id');
    }

    public function getTargetIdsByUserAndType($userId, array $types)
    {
        return $this->model->where(['user_id' => $userId])->whereIn('type', $types)->pluck('target_id')->toArray();
    }

    public function deleteExpirePack()
    {
        $this->model->query()->where('expire', '!=', 0)->where('expire', '<=', Carbon::now()->timestamp)->delete();
        return true;
    }

    public function unUseOldPack($userId)
    {
        $this->model->where('user_id', $userId)->update(['is_used' => 0]);
    }


    //    public function packsJoinWithGift($userId, $type): \Illuminate\Support\Collection
    //    {
    //        return $this->model->join('gifts as b', 'packs.target_id', '=', 'b.id')
    //            ->where(['packs.user_id' => $userId, 'packs.type' => $type])
    //            ->selectRaw("packs.*,b.name,b.show_img,b.price")
    //            ->get();
    //    }

    public function packsJoinWithGift($userId, $type, array $additionalRelations = []): Collection|array
    {
        return $this->model
            ->with(array_merge(['gift:id,name,show_img,price'], $additionalRelations))
            ->where('user_id', $userId)
            ->where('type', $type)
            ->get();
    }

    //    public function packsJoinWithWare($userId, $type): \Illuminate\Support\Collection
    //    {
    //        return $this->model->join('wares as b', 'packs.target_id', '=', 'b.id')
    //            ->where(['packs.user_id' => $userId, 'packs.type' => $type])
    //            ->selectRaw("packs.*,b.name,b.show_img,b.title,b.color, b.img2 as img2")
    //            ->get();
    //    }

    public function packsJoinWithWare($userId, $type, array $additionalRelations = []): Collection|array
    {
        return $this->model
            ->with(array_merge(['ware:id,id,name,show_img,title,color,img2,image_type'], $additionalRelations))
            ->where('user_id', $userId)
            ->where('type', $type)
            ->get();
    }

    public function getByUserId($userId, $packId)
    {
        return $this->model->where(['user_id' => $userId, 'id' => $packId])->first();
    }

    public function updateIsUsedByType($userId, $type)
    {
        $this->model->where('user_id', $userId)->where('type', $type)->update([
            'is_used' => 0
        ]);
        return true;
    }

    // public function updateIsUsedByPackId($userId, $packId,$expire)
    // {
    //     $this->model->query()->where(['user_id' => $userId, 'id' => $packId])->update(['is_used' => 1, 'use_num' => 1, 'using'=> 1,'expire' =>$expire ]);
    //     return true;
    // }
    public function updateIsUsedByPackId(int $userId, int $packId, string $expire): bool
    {
        return DB::transaction(function () use ($userId, $packId, $expire) {
            $pack = $this->model->query()
                ->where(['user_id' => $userId, 'id' => $packId])
                ->lockForUpdate()
                ->first();

            if (!$pack) {
                return false;
            }

            $this->isFirstUse($pack)
                ? $this->markFirstUse($pack, $expire)
                : $this->incrementUse($pack);

            return true;
        });
    }

    private function isFirstUse($pack): bool
    {
        return (int) $pack->using === 0;
    }

    private function markFirstUse($pack, string $expire): void
    {
        $pack->update([
            'is_used' => 1,
            'use_num' => 1,
            'using'   => 1,
            'expire'  => $expire,
        ]);
    }

    private function incrementUse($pack): void
    {
        $pack->update([
            'is_used' => 1,
            'use_num' => $pack->use_num + 1,
        ]);
    }


    public function  bestSale()
    {
        return $this->model->select('target_id', \DB::raw('SUM(num) as total_num'))->groupBy('target_id')->with('ware')->orderByDesc('total_num')->limit(10)->get();
    }

    public function userPacks($type, $userId, $perPage, $page)
    {
        return $this->model->where('user_id', $userId)
            ->when(isset($type), function ($query) use ($type) {
                $query->where('type', $type);
            })->active()->paginate($perPage, ['*'], 'page', $page);
    }

    public function pack($id, $userId)
    {
        return $this->model->query()->where('id', $id)->where('user_id', $userId)->where('is_used', 0)->where(function ($q) {
            $q->where('expire', 0)->orWhere('expire', '>=', now()->timestamp);
        })->first();
    }

    public function UnusedPack($id, $userId)
    {
        return $this->model->query()->where('id', $id)->where('user_id', $userId)->where('is_used', 0)->where('using', 0)->whereNull('expire')->first();
    }
}
