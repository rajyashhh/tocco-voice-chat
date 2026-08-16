<?php

namespace App\Tik\Repositories;

use Modules\AgencyApp\Entities\LeaveAgencyRequest;


class LeaveAgencyRequestRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new LeaveAgencyRequest());
    }

    public function getOldRequest($agencyId, $userId)
    {
        return $this->model->query()->where(['agency_id' =>  $agencyId, 'user_id'   =>  $userId, 'status'    =>  0])->first();
    }

    public function getRequest($userId, $agencyId)
    {
        return $this->model->query()->where(['user_id' => $userId, 'agency_id' => $agencyId])->first();
    }
}
