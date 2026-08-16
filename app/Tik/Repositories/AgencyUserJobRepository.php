<?php

namespace App\Tik\Repositories;

use Modules\AgencyApp\Entities\AgencyUserJob;


/** @property AgencyUserJob $model*/
class AgencyUserJobRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new AgencyUserJob());
    }

    public function findByUserId($userId)
    {
        return $this->model->where('user_id', $userId)->where('type', 'requestManger')->first();
    }

    public function exists(int $userId, int $agencyId)
    {
        return $this->model->where('user_id', $userId)->where('agency_id', $agencyId)->where('type', 'requestManger')->exists();
    }

    public function delete(int $userId)
    {
        return $this->model->where('user_id', $userId)->where('type', 'requestManger')->delete();
    }

    public function deleteAdmin(int $userId ,$agency_id)
    {
        return $this->model->where('user_id', $userId)->where('agency_id', $agency_id)->delete();
    }
}
