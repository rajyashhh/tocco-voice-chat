<?php

namespace App\Tik\Repositories;

use App\Models\UserSetting;


class UserSettingRepository extends AbstractRepository
{

    public function __construct()
    {
        parent::__construct(new UserSetting());
    }

    public function userSitting($userId)
    {
        return $this->model->where("user_id", $userId)->first();
    }

    public function updateKey($userSitting, $key)
    {
        $userSitting->key = $key;
        $this->updateSitting($userSitting);
    }

    public function updateSitting($userSitting)
    {
        $userSitting->save();
        return true;
    }
}
