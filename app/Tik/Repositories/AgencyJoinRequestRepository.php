<?php

namespace App\Tik\Repositories;

use App\Models\AgencyJoinRequest;

class AgencyJoinRequestRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new AgencyJoinRequest());
    }

    public function countByMonth($userId)
    {
        return $this->model->query()->where('user_id', $userId)->where('status', '!=', 2)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
    }

    public function countByAgency($userId, $agencyId)
    {
        return $this->model->query()->where('agency_id', $agencyId)->where('user_id', $userId)->where('status', '=', 0)->count();
    }

    public function getByUser($userId)
    {
        return $this->model->query()->where('user_id', $userId)->get();
    }

    public function getByAgencyId($agencyId)
    {
        $requests = $this->model->query()->where('agency_id', $agencyId)->where('status', 0)->whereHas('user')->with('user')->get();
        return $requests->pluck('user');
    }

    public function findRequest($userId, $agencyId)
    {
        return $this->model->where('agency_id', $agencyId)->where('status', 0)->where('user_id', $userId)->first();
    }

    public function findAcceptRequest($userId)
    {
        return $this->model->where('status', 1)->where('user_id', $userId)->first();
    }

    public function findByUsersAndAgency($userId, $agencyId)
    {
        return $this->model->where(['user_id' => $userId, 'agency_id' => $agencyId])->first();
    }

    public function allRequests($status, $agencyId, $id, $perPage, $page)
    {
        return $this->model->when(isset($status), function ($query) use ($status) {
            $query->where('status', $status);
        })->when(isset($id), function ($query) use ($id) {
            $query->where('id', $id);
        })->whereHas('agency', function ($q) use ($agencyId) {
            $q->when(isset($agencyId), function ($query) use ($agencyId) {
                $query->where('id', $agencyId);
            });
        })->with('user', 'agency', 'admin')->paginate($perPage, ['*'], 'page', $page);
    }
}
