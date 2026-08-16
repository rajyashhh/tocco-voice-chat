<?php

namespace App\Helpers;

use App\Events\AdminNotificationCreated;
use App\Jobs\SendFirebaseNotificationsToAdmins;
use App\Models\AdminNotification;
use App\Enums\AdminNotificationType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Admin;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Google_Client;
class AdminNotificationHelper
{
    public static function notify(
        AdminNotificationType $type,
        string $title,
        ?string $message = null,
        $model = null,
        ?array $data = [],
        ?int $adminId = null
    ): AdminNotification {
        $notification = AdminNotification::create([
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'model_id' => $model?->id,
            'model_type' => $model ? get_class($model) : null,
            'data' => $data ?? [],
            'admin_id' => $adminId,
        ]);
        $url='';
        $previewUrl = $data['preview_url'] ?? null;
        if ($previewUrl) {
            $enum = \App\Enums\AdminNotificationLink::tryFrom($previewUrl?->value);
            if ($enum) {
                $url = $enum->url($data);
            }
        }

        broadcast(new AdminNotificationCreated($notification))->toOthers();

        SendFirebaseNotificationsToAdmins::dispatch($title, $message, $url)->onQueue('notification');


        return $notification;

    }

    public static function markAsRead(AdminNotification $notification): void
    {
        $notification->update(['is_read' => true, 'read_at' => now()]);
    }

   
    public static function unreadCount(?int $adminId = null): int
    {
        $adminId = $adminId ?? Auth::id();

        return AdminNotification::
            where('is_read', false)
            ->count();
    }

    public static function sendNotification($token, $title, $body, $url)
    {
        // $projectId = env('FIREBASE_PROJECT_ID'); 
        $projectId = env('FIREBASE_PROJECT_NAME'); 

        $client = new Google_Client();
        $client->setAuthConfig(Common::firebaseCredentials());

        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken()['access_token'];

        $payload = [
            "message" => [
                "token" => $token,
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                ]
                ,
                "data" => [ 
                    "click_action" => $url
                ]
            ]
        ];
     
        $response = Http::withHeaders([
            "Authorization" => "Bearer $accessToken",
            "Content-Type" => "application/json",
        ])->post("https://fcm.googleapis.com/v1/projects/$projectId/messages:send", $payload);
      
        return $response->json();
    }

  

}


