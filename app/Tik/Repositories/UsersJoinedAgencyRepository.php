<?php

namespace App\Tik\Repositories;

use App\Models\UsersJoinedAgency;


class UsersJoinedAgencyRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new UsersJoinedAgency());
    }

    public function exist($userId, $agencyId)
    {
        return $this->model->where('user_id', $userId)->where('agency_id', $agencyId)->where('leave_date', null)->exists();
    }

    public function findByUser($userId, $agencyId)
    {
        return $this->model->where('user_id', $userId)->where('agency_id', $agencyId)->where('type', 2)->where('leave_date', null)->first();
    }
}
