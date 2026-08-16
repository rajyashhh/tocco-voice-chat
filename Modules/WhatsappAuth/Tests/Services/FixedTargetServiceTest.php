<?php

namespace Modules\WhatsappAuth\Tests\Services;

use Carbon\Carbon;
use Modules\FixedTarget\Services\FixedTargetService;
use PHPUnit\Framework\TestCase;

class FixedTargetServiceTest extends TestCase
{

    public function testCalculateTarget()
    {

        $timezone = 'Africa/Cairo';
        $now = Carbon::now();
        $end = Carbon::now($timezone)->endOfMonth()->timezone('UTC');

        echo $end . PHP_EOL;
        echo 'now '. $now;

    }
}
