<?php

namespace App\Services;

use App\Repositories\ConfigRepository;

class ConfigService
{
    protected $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function getAllConfigs()
    {
        return $this->configRepository->getAll();
    }
}
