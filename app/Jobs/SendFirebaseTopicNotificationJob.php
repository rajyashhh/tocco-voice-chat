<?php

namespace App\Jobs;

use App\Tik\DTO\NotificationPayload;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFirebaseTopicNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected NotificationPayload $payload)
    {
        //
    }

    public function handle(): void
    {
        if (count($this->payload->tokens) <= 1) {
            return;
        }

        Common::send_firebase_notification_top(
            tokens: $this->payload->tokens,
            title: $this->payload->title,
            body: $this->payload->body,
            icon: $this->payload->icon,
            data: $this->payload->data,
            messageType: $this->payload->messageType,
            user: $this->payload->user,
            action: $this->payload->action,
            type: $this->payload->type,
            id: $this->payload->id,
            notification_type: $this->payload->notificationType
        );
    }
}
