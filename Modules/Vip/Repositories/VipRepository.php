<?php

namespace  Modules\Vip\Repositories;

use Illuminate\Support\Facades\Cache;
use Modules\Vip\Entities\Vip;
use App\Tik\Repositories\AbstractRepository;

class VipRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Vip());
    }

    public function getByType($type)
    {
        return $this->model->where('type', $type)->paginate(15);
    }

    public function getByLevels($levelsList, $type)
    {
        return $this->model->query()->whereIn('level', $levelsList)
            ->where('type', $type)->select('img', 'level')->get();
    }

    public function getByLevelsV2($levelsList, $type)
    {
        return $this->model::collectionBuilder()->whereIn("level", $levelsList)->where('type', $type)->get();
    }
    public function badgesVip($type)
    {
        return $this->model
            ->where('type', $type)
            ->where('level', '!=', 0)
            ->where(function ($query) {
                $query->whereRaw('(level % 10 = 0)')->orWhere('level', 1);
            })
            ->orderBy('level')
            ->get();
    }

    public function findByLevel($level, $type)
    {
        return $this->model->where("level", $level)->where('type', $type)->orderByDesc('level')->first();
    }

    public function nextLevel($level, $type)
    {
        return $this->model->where("type", $type)->where("level", ">", $level)->orderBy('id')->first();
    }

    public function findByType($type)
    {
        return $this->model->where("type", $type)->orderBy('level')->first();
    }

    public function getLevelGroups(): array
    {
        return Cache::remember('levels_chunks', 3600, function () {
            return [
                'sender' => $this->mapChunk($this->getLevelsByType(2)),
                'receiver' => $this->mapChunk($this->getLevelsByType(1)),
                'charge' => $this->mapChunk($this->getLevelsByType(5)),
            ];
        });
    }

    public function getRoomLevel(): array
    {
        return Cache::remember('room_levels_all', 3600, function () {
            return [
                'room_level' => $this->mapAllLevels($this->getLevelsByType(4)),
            ];
        });
    }

    private function mapAllLevels($vips)
    {
        return $vips->map(function ($vip) {
            return [
                'level' => $vip->level,
                'badge' => $vip->img,
                'exp'   => $vip->exp ?? 0,
            ];
        })->toArray();
    }

    private function mapChunk($vips)
    {
        return $vips->chunk(10)->map(function ($chunk) {
            return [
                'minlevel' => $chunk->first()->level,
                'maxlevel' => $chunk->last()->level,
                'badge'    => $chunk->last()->img,
            ];
        })->toArray();
    }

    public function getLevelsByType(int $type)
    {
        return $this->model->where('type', $type)->orderBy('level')->get();
    }
}
