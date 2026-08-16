<?php

namespace App\Jobs;

use App\Models\User;
use Modules\FixedTarget\Services\FixedTargetV2Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class CalculateUserTargetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private User $user,
        private ?int $month = null,
        private ?int $year = null
    ) {}

    public function handle(): void
    {
        $service = new FixedTargetV2Service($this->user, $this->month, $this->year);
        $service->calculateTarget();
    }
}
