<?php

namespace App\Jobs;

use App\Facades\RedisService;
use GuzzleHttp\Client;
use Database\Seeders\config;
use Illuminate\Bus\Queueable;
use PHPUnit\Event\Telemetry\Info;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SlowTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        sleep(5);
    }
}
