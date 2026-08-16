<?php

namespace Modules\Vip\Repositories;

use App\Models\Pack;
use Modules\Vip\Entities\UserVip;
use App\Tik\Repositories\AbstractRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;


/**
 *@property UserVip $model
 */
class UserVipRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new UserVip());
    }

    public function findByUserId($userId)
    {
        return $this->model->where('user_id', $userId)->orderBy('expire', 'DESC')->first();
    }

    public function getAllByUserId($userId)
    {
        return $this->model->where('user_id', $userId)->with('OVip')->where(function ($query) {

            $query->where('expire', '>', Carbon::now()->timestamp)->orWhereNull('expire')->orWhere('expire', 0);
        })->orderBy('id')->get();
    }

    public function getAllByUserIdWithAll($userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->with(['OVip.privilegs'])
            ->where('expire', '>', Carbon::now()->timestamp)
            ->orderBy('expire', 'DESC')
            ->get();
    }

    public function deleteExpireUserVip()
    {
        $this->model->query()->where('expire', '!=', 0)->where('expire', '<', Carbon::now()->timestamp)->delete();
        return true;
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function getByUserId($userId,array $additionalRelations = []): Collection
    {
        $userQuery = $this->model->where("user_id", $userId);
        $userQuery = $additionalRelations ? $userQuery->with($additionalRelations) : $userQuery;
        return $userQuery->get();
    }

    public function findByIdWithOVip($id)
    {
        return $this->model->with("OVip")->has("OVip")->find($id);
    }
    public function findByIdWithPack($id,$user_id)
    {
        return Pack::where('id', $id)
        ->where('user_id', $user_id)
        ->where(function ($q) {
            $q->where('expire', 0)
              ->orWhere('expire', '>=', time());
        })
        ->first();
    }
    public function togglePackUsage($pack_id, $user_id, bool $isUsed): bool
    {
        $pack = $this->findByIdWithPack($pack_id, $user_id);

        if (!$pack) {
            throw new \Exception(__("pack_not_found"));
        }
        if ($pack->is_used != $isUsed) {
            $pack->is_used = $isUsed;
            $pack->save();
        }

        return true;
    }

    public function updateIsUsedForUser($userId)
    {
        $this->model->where('user_id', $userId)->update(['is_used' => 0]);
        $vips = $this->model->where('user_id', $userId)->get();

        foreach ($vips as $vip) {
            $vip->packs()->update(['is_used' => 0]);
        }
    }
    public function updateTrueIsUsedForUser($userId)
    {
        $userPacks = Pack::where('user_id', $userId)->get();

        $vips = $this->model->where('user_id', $userId)->with('OVip.privilegs')->get();

        if ($vips->isEmpty()) {
            return;
        }
        $vipFeatureTypes = collect();
        $vipPackIds = collect();

        foreach ($vips as $vip) {
            $vipModel = $vip->OVip;

            if ($vipModel && $vipModel->privilegs) {
                foreach ($vipModel->privilegs as $privilege) {
                    if (!is_null($privilege->type)) {
                        $vipFeatureTypes->push($privilege->type);

                        $matchingPack = Pack::where('user_id', $userId)
                            ->where('type', $privilege->type)
                            ->orderByDesc('created_at')
                            ->first();

                        if ($matchingPack) {
                            $vipPackIds->push($matchingPack->id);
                        }
                    }
                }
            }
        }

        $vipFeatureTypes = $vipFeatureTypes->unique();

        foreach ($userPacks as $pack) {
            $isSameTypeAsVip = $vipFeatureTypes->containsStrict($pack->type);
            $isVipPack        = $vipPackIds->contains($pack->id);

            if ($isSameTypeAsVip && !$isVipPack) {
                $pack->update(['is_used' => 0]);

            }

            if ($isVipPack) {
                $pack->update(['is_used' => 1]);

            }
        }
    }
    public function updateIsUsed($userVip, $isUsed)
    {
        $userVip->is_used = $isUsed;
        $userVip->using  = 1;
        $this->updateUserVip($userVip);
    }
    public function updateNumUsed($userVip)
    {
        $userVip->num_used += 1;
        $this->updateUserVip($userVip);
    }

    public function updateIsUsedWithNum($userVip, $isUsed)
    {
        $this->updateNumUsed($userVip);
        $this->updateIsUsed($userVip, $isUsed);
    }

    public function updateUserVip($userVip)
    {
        $userVip->save();
        return true;
    }

    public function findByUserLevel($userId, $level, $vipId)
    {
        return $this->model->where('user_id', $userId)->where('level', $level)->where('vip_id', $vipId)->where(function ($query) {
            $query->where('expire', '!=', 0)->where('expire', '>', Carbon::now()->timestamp)->orWhere('expire', 0);
        })->first();
    }

    public function createUserVip(array $data)
    {
        return UserVip::create($data);
    }

    public function deleteExpiredVips($userId, $level)
    {
        return UserVip::where('user_id', $userId)
            ->where('level', '<=', $level)
            ->delete();
    }

    public function findUserVipWithOVip($vipId)
    {
        return UserVip::with('OVip')->has('OVip')->find($vipId);
    }

    public function updateUserVipIsUsed($userId, $isUsed)
    {
        return UserVip::where('user_id', $userId)->update(['is_used' => $isUsed]);
    }

    public function saveUserVip($userVip)
    {
        $userVip->save();
    }

    public function findUserVipById($vipId)
    {
        return UserVip::find($vipId);
    }
}
