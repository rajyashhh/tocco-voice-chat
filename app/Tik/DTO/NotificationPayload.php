<?php

namespace App\Tik\DTO;

use Illuminate\Support\Collection;

class NotificationPayload
{
    public array $tokens;

    public function __construct(
        array|Collection $tokens,
        public string $title,
        public string $body,
        public string $icon = '',
        public array $data = [],
        public ?string $messageType = null,
        public $user = null,
        public string $action = '',
        public string $type = '',
        public string $id = '',
        public string $notificationType = 'user_notification',
    ) {
        $this->tokens = $tokens instanceof Collection ? $tokens->toArray() : $tokens;
    }
}
