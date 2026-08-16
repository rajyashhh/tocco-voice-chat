<?php

namespace App\Tik\Repositories;

use App\Models\FamilyUser;



class FamilyUserRepository extends AbstractRepository
{

    /**
     * @param Model $model
     */
    public function __construct()
    {
        parent::__construct(new FamilyUser());
    }


    // public function create($data)
    // {
    //     return $this->model->create([
    //         'user_id' => $data['user_id'],
    //         'family_id' => $data['family_id'],
    //         'user_type' => $data['user_type'],
    //         'status' => $data['status'],
    //     ]);
    // }

    public function checkIsAdmin($familyId, $userId)
    {
        return $this->model->query()->where('user_type', 1)
            ->where('user_id', $userId)
            ->where('family_id', $familyId)
            ->where('status', 1)->exists();
    }

    public function admin($userId)
    {
        return $this->model->query()->where('user_type', 1)
            ->where('user_id', $userId)->where('status', 1)->first();
    }

    public function checkSendJoinRequest($userId, $familyId)
    {
        return $this->model->where('user_id', $userId)->where('family_id', $familyId)->where('status', 0)->exists();
    }

    public function checkFamilyMember($userId, $familyId)
    {
        return $this->model->where('user_id', $userId)->where('family_id', $familyId)->where('status', 1)->exists();
    }

    public function delete($familyId)
    {
        $this->model->where('family_id', $familyId)->delete();
        return true;
    }
    public function deleteByUserId($userId)
    {
        $this->model->where('user_id', $userId)->delete();
        return true;
    }

    public function deleteUserFromFamily($userId, $familyId)
    {
        $this->model->where('family_id', $familyId)->where('user_id', $userId)->delete();
        return true;
    }

    public function requestUsersList($userId, $familyId)
    {
        return $this->model->query()
            ->with(['user' => fn($q) => $q->with([
                'profile:id,user_id,avatar,birthday,gender',
                'country:id,name,flag',
                'specialId.ware',
                'mangerType',
            ])])
            ->where('family_id', $familyId)
            ->where('user_id', '!=', $userId)->where('status', 0)->get();
    }

    public function findById($id)
    {
        return $this->model->find($id);
    }

    public function findByUserId($userId)
    {
        return $this->model->query()->where('user_id', $userId)->where('status', 1)->first();
    }

    public function deleteOldRequest($userId, $acceptRequestId)
    {
        $this->model->where('user_id', $userId)->where('status', 0)->where('id', '!=', $acceptRequestId)->delete();
        return true;
    }

    public function deleteRefusedRequest($refusedRequestId)
    {
        $this->model->where('id', $refusedRequestId)->delete();
        return true;
    }

    public function getFamilyMember($familyId, $userId)
    {
        return $this->model->where('family_id', $familyId)->where('user_id', '!=', $userId)->get();
    }


    public function getFamilyUser($userId, $familyId)
    {
        return $this->model->where('user_id', $userId)->where('family_id', $familyId)->where('status', 1)->first();
    }

    public function adminIds($familyId)
    {
        return $this->model->where('family_id', $familyId)->where('status', 1)->where('user_type', 1)->pluck('user_id');
    }

    public function memberIds($familyId)
    {
        return $this->model->where('family_id', $familyId)->where('status', 1)->where('user_type', 0)->pluck('user_id');
    }

    public function familyMemberIds($familyId)
    {
        return $this->model->query()->where('family_id', $familyId)->where('status', 1)->pluck('user_id')->toArray();
    }


    public function exitUser($userId)
    {
        $this->model->query()->where('user_id', $userId)->delete();
    }
}
