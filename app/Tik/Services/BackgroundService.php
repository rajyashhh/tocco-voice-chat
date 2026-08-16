<?php

namespace App\Tik\Services;

use App\Tik\Repositories\BackgroundRepository;

class BackgroundService
{
    public function __construct(
        private readonly BackgroundRepository $backgroundRepository,
    ) {
    }


    public function index()
    {
        return $this->backgroundRepository->index();
    }
}
