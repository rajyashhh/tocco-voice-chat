<?php

namespace App\Jobs;

use App\Classes\Enums\NotificationType;
use App\Facades\CustomNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCustomOfficialMessageToUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $id, private NotificationType $notificationType)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        switch ($this->notificationType){
            case NotificationType::RECEIVED_LEVEL:
                CustomNotification::receiverLevel($this->id);
                break;
            case NotificationType::SENDER_LEVEL:
                CustomNotification::senderLevel($this->id);
                break;
            case NotificationType::TARGET:
                CustomNotification::target($this->id);
                break;
            case NotificationType::FAMILY:
                CustomNotification::familyLevelUpgrade($this->id);
                break;
        }
    }
}
