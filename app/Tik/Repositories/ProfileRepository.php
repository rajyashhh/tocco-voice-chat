<?php

namespace App\Tik\Repositories;

use App\Models\Profile;

class ProfileRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Profile());
    }


}
