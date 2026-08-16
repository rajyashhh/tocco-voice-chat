<?php

namespace App\Services;

use App\Repositories\LevelRepository;
use Illuminate\Support\Facades\Cache;

class LevelService
{
    protected $levelRepo;

    public function __construct(LevelRepository $levelRepo)
    {
        $this->levelRepo = $levelRepo;
    }

    public function getAllLevelsRanges(): array
    {
        $cacheKey = "levels_range";

        $data = Cache::get($cacheKey);
        if ($data != null) {
            return $data;
        }

        $receiverData = $this->getLevelData(1, 10);
        $senderData = $this->getLevelData(2, 10);

        $data = ['sender' => $senderData, 'receiver' => $receiverData];
        Cache::put($cacheKey, $data, 24 * 60 * 60);
        return $data;
    }

    private function getLevelData($type, $patternNum): array
    {
        $levels = $this->levelRepo->getLevelsByTypeAndPattern($type, $patternNum);

        $maxLevels = floor($levels->count());
        $data = [];
        $startLevel = 1;

        for ($i = 1; $i <= $maxLevels; $i++, $startLevel = ($i - 1) * $patternNum + 1) {
            $content = 'LV' . $startLevel . '-LV' . ($i * $patternNum);

            $data[] = [
                'image' => $levels->where('level', $startLevel)->first()?->img ?? '',
                'content' => $content
            ];
        }

        return $data;
    }
}