<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Classes\Gifts\SendGiftService;

class UpdateFamilyLevelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $receivedUsers;
    public $totalCoinsPerUser;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($receivedUsers, $totalCoinsPerUser)
    {
        // $this->receivedUsers must be an Eloquent Collection or Array
        $this->receivedUsers = $receivedUsers;
        $this->totalCoinsPerUser = $totalCoinsPerUser;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SendGiftService $sendGiftService)
    {
        $sendGiftService->updateFamilyLevelForReceiver(
            $this->receivedUsers,
            $this->totalCoinsPerUser
        );
    }
}
