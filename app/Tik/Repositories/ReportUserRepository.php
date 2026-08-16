<?php

namespace App\Tik\Repositories;
use App\Models\Report_user;


class ReportUserRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new Report_user());
    }
}