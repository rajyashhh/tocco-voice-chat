<?php

namespace Modules\FixedTarget\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface TargetInterface
{
    public function getTarget(int $diamond) : Model|null;
    public function calculateUsdFromTarget(Model $target, float $hours, int $days, array $extra ) : float;
    public function calculatePercentageAchieved(Model $target, float $hours, int $days, array $extra ) : float;
    
}
