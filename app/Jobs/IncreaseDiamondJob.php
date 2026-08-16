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

class IncreaseDiamondJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(private int $userId, private float $value)
    {}

    public function handle(): void
    {
        \DB::transaction(fn() => incrementMonthlyDiamond($this->userId, $this->value));

    }
}
