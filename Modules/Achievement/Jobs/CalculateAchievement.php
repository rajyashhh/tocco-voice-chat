<?php

namespace Modules\Achievement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Achievement\Http\Services\UserAchievementService;
use App\Models\Gift;
use App\Models\User;
class CalculateAchievement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $gift;
    protected $number;
    protected $room_owner;

    public function __construct(Gift $gift,$number,User $room_owner)
    {
        $this->gift = $gift;
        $this->number = $number;
        $this->room_owner = $room_owner;
    }

    public function handle()
    {
        $userAchievementService = new UserAchievementService();
        if($this->gift?->type == 5 && $this->gift?->achievement){
            $userAchievementService->giftTarget($this->gift, $this->number);
        }

        $userAchievementService->roomTarget($this->room_owner, ($this->number * $this->gift?->price));
    }
}
