<?php

namespace App\Tik\Repositories;

use App\Models\FamilyRank;




class FamilyRankRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new FamilyRank());
    }

    public function ranking(array $condition = [], array $whereBetween = [], int $limit = 3, $paginate = null)
    {
        $builder = $this->model->query()->selectRaw('sum(coins) as coins,family_id')
            ->groupBy('family_id')->whereHas('family')
            ->with(['family' => fn($q) => $q->with(['owner' => fn($q) => $q->with('country')])])
            ->where($condition);

        foreach ($whereBetween as $key => $value) {
            $builder->whereBetween($key, $value);
        }
        if ($paginate) {
            $data =  $builder->orderByDesc("coins")->paginate($paginate);
        } else {
            $data = $builder
                ->take($limit)
                ->get();
        }

        return $data;
    }
}
