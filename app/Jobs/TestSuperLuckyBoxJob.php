<?php

namespace App\Jobs;

use Carbon\Carbon;
use App\Models\Room;
use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Facades\CustomNotification;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Http\Services\LuckyBoxServices;
use Illuminate\Queue\InteractsWithQueue;
use Modules\LuckyBox\Services\BoxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Modules\LuckyBox\Http\Controllers\BoxController;

class TestSuperLuckyBoxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $requestData) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $users = User::get()->take(20);

        foreach ($users as $user) {
            // Your sending logic here
            $this->send($this->requestData, $user);
        }
    }

    private function send(array $requestData, $user)
    {
        $boxService = new BoxService();
        $request = new \Illuminate\Http\Request($requestData);
        (new BoxController($boxService))->sendTest($request, $user);
    }
}
