<?php

namespace App\Tik\Repositories;

use App\Models\Ware;
use Illuminate\Support\Facades\Cache;
use Modules\Vip\Entities\Vip;


class WareRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new Ware());
    }


    public function getDressWare($id)
    {
        return $this->model->where(['id' => $id])->first();
    }

    public function getWithType($type, $level)
    {
        return $this->model->query()->where('get_type', 1)
            ->where('type', $type)
            ->select('id', 'img2')
            ->where('level', $level)
            ->first();
    }

    /**
     * Batched version of getWithType(): fetch the first matching ware (by id,
     * which mirrors the implicit primary-key ordering of the per-row first())
     * for each (type, level) pair in a single query. Returns a nested map
     * keyed by type then level so callers can resolve in PHP without N+1.
     */
    public function getWithTypesAndLevels(array $types, array $levels)
    {
        if (empty($types) || empty($levels)) {
            return [];
        }

        $rows = $this->model->query()->where('get_type', 1)
            ->whereIn('type', $types)
            ->whereIn('level', $levels)
            ->select('id', 'img2', 'type', 'level')
            ->orderBy('id')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            if (!isset($map[$row->type][$row->level])) {
                $map[$row->type][$row->level] = $row;
            }
        }

        return $map;
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function checkWare($type)
    {
        return $this->model->where('type', $type)->exists();
    }
    public function all($userId = 0, $type)
    {
        $wares = $this->model->query()
            ->where('enable', 1)
            ->whereIn('get_type', [4, 6])
            ->where('type', $type);
        if ($type == 25) {

            $wares = $wares->isNotUsedInPacks()->showUserCustom($userId);
        }
        return $wares->get();
    }

    public function allWithType( $type, $userId = null)
    {
        $wares = $this->model->query()
            ->where('type', $type);
        if ($type == 25) {

            if (!$userId) {
                $wares = $wares->isNotUsedInPacks()->showUserCustom($userId);
            }
        }
        return $wares->get();
    }

    public function getById($wareId)
    {
        return $this->model->query()->where('id', $wareId)
            ->where('enable', 1)->whereIn('get_type', [4, 6])->first();
    }

    public function getWaresByConditions($vipLevel, array $types, array $ids)
    {
        return $this->model
            ->where(['get_type' => 1, 'enable' => 1])
            ->where('level', '<=', $vipLevel)
            ->whereIn('type', $types)
            ->whereNotIn('id', $ids)
            ->selectRaw('id,type,expire')
            ->get();
    }

    public function countWareByLevel($level)
    {
        return $this->model->query()->where('get_type', 1)->where('enable', 1)->where('level', $level)->where('is_active_for_vip', 1)->count();
    }

    public function getOVip($levels = [], $types = [])
    {
        return $this->model->query()->where('get_type', 1)->whereIn('type', $types)->whereIn('level', $levels)/*->where('enable', true)*/->get();
    }

    public function getOVipNew($levels = [], $types = [])
    {
        return $this->model->query()->where('get_type', 1)->whereIn('type', $types)->whereIn('level', $levels)->where('enable', true)->get();
    }

    public function notActive($level)
    {
        return  $this->model->where('level', $level)->update([
            'is_active_for_vip' => false
        ]);
    }

    public function updateActiveWithType($type, $level)
    {
        return $this->model->where('type', $type)->where('level', $level)->update([
            'is_active_for_vip' => true
        ]);
    }

    public function getByTypeAndLevel($type, $level)
    {
        return $this->model->where(['type' => $type, 'get_type' => 1, 'level' => $level])->get();
    }

    public function findByTypeAndLevel($typePrivilege, $levelOvip)
    {
        return $this->model->where(['type' => $typePrivilege, 'get_type' => 1, 'level' => $levelOvip])->first();
    }

    public function allWares($page, $perPage)
    {
        return $this->model->whereNot('get_type', 1)->paginate($perPage, ['*'], 'page', $page);
    }
    public function profile_frame_wares($page, $perPage)
    {
        return $this->model->where('type', 28)->orderByDesc('is_active_for_vip')->select(['id', 'img2', 'level', 'image_type', 'half_image_profile'])->get();
    }
    public function giftOVip($level, $type)
    {
        return $this->model->where('level', $level)->where('get_type', 1)->where('type', $type)->where('is_active_for_vip', 1)->first();
    }


    public function getFromType(int $type, int $pagination = 10)
    {
        return $this->model->where('type', $type)->paginate($pagination);
    }

    public function getAllFromType(int $type)
    {
        return $this->model->where('type', $type)->get();
    }
}
