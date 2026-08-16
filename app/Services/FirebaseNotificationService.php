<?php

namespace App\Services;

use App\Helpers\Common;
use Kreait\Firebase\Factory;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $firebase = (new Factory)
            ->withServiceAccount(Common::firebaseCredentials());

        $this->messaging = $firebase->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body)
    {
        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        return $this->messaging->send($message);
    }
}
