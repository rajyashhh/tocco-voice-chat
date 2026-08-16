<?php

namespace App\Tik\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;


abstract class AbstractRepository
{
    /**
     * @param Model $model
     */
    public function __construct(protected Model $model)
    {
    }

    /**
     * @param array $condition
     * @param array $data
     * @return mixed
     */
    public function updateOrCreate(array $condition, array $data): mixed
    {
        return $this->model->updateOrCreate($condition,$data);
    }

    /**
     * @param array $data
     * @param $id
     * @return mixed
     */
    public function update(array $data, $id): mixed
    {
        if ($id instanceof Model){
            return $id->update($data);
        }
        return $this->model->findOrFail($id)->update($data);
    }


    /**
     * @param array $data
     * @return mixed
     */
    public function create(array $data): mixed
    {
        return $this->model->create($data);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function insert(array $data): mixed
    {
        return $this->model->insert($data);
    }

    /**
     * @param int $id
     * @param array $relations
     * @return Model|Collection|Builder|array|null
     */
    public function findOrFail(int $id,array $relations = []): Model|Collection|Builder|array|null
    {
        return $this->model->with($relations)->findOrFail($id);
    }

    /**
     * @param array $conditions
     * @param array $with
     * @param array $select
     * @param array $orderBy
     * @param array $withCount
     * @return mixed
     */
    public function getPaginate(array $conditions = [], array $with = [], array $select = ['*'],array $orderBy = ['id' => 'desc'], array $withCount =[]): mixed
    {
        return $this->prepareQuery($conditions,$with,$select)->withCount($withCount)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                foreach ($orderBy as $key=>$value) {
                    $query->orderBy($key,$value);
                }
            })->paginate(config('app.element_number_per_page'));
    }

    /**
     * @param array $conditions
     * @param array $with
     * @param array $select
     * @param array $orderBy
     * @return mixed
     */
    public function getAll(array $conditions = [] ,array $with = [],array $select = ['*'], array $orderBy = ['id' => 'desc']): mixed
    {
        return $this->prepareQuery($conditions,$with,$select)
            ->when(!empty($orderBy), function ($query) use ($orderBy) {
                foreach ($orderBy as $key=>$value) {
                    $query->orderBy($key,$value);
                }
            })->get();
    }

    /**
     * @param array $conditions
     * @param array $with
     * @param array $select
     * @return Builder
     */
    public function prepareQuery(array $conditions = [], array $with = [], array $select = []): Builder
    {
        return $this->model->with($with)->where($conditions)->select($select);
    }

    public function searchWith(string $search, $column = 'name') : mixed
    {
        return $this->model->where($column, 'like','%'. $search . '%')->select(['name as text', 'id'])->take(10)->get();
    }

    public function delete(int $id)
    {
        $data= $this->model->find($id);
        if (!$data) return false;
        $data->delete();
        return true;
    }

    public function deleteAth($id,$user_id = 0)
    {
        $data = $this->model->find($id);
        if (!$data) return false;

        $data->delete();
        return true;
    }


}
