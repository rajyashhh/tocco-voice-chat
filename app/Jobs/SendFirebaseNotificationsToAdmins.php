<?php

namespace App\Jobs;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Helpers\Common;
use Google_Client;

class SendFirebaseNotificationsToAdmins implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $title;
    protected ?string $body;
    protected ?string $url;

    /**
     * Create a new job instance.
     */
    public function __construct(string $title, ?string $body = null, ?string $url = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->url = $url ?? '';
    }

   
    public function handle(): void
    {

        $projectId = env('FIREBASE_PROJECT_NAME');
        $client = new Google_Client();
        $client->setAuthConfig(Common::firebaseCredentials());
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken()['access_token'];

        $admins = Admin::where('type', 'employee')->whereNotNull('fcm_token')->get();


        foreach ($admins as $admin) {
            $token = $admin->fcm_token;

            $payload = [
                "message" => [
                    "token" => $token,
                    "notification" => [
                        "title" => __($this->title),
                        "body" => __($this->body),
                    ],
                    "data" => [
                        "click_action" => $this->url,
                    ],
                ],
            ];

            $response = Http::withHeaders([
                "Authorization" => "Bearer $accessToken",
                "Content-Type" => "application/json",
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if (!$response->successful()) {
                logger()->error('🔴Firebase', [
                    'admin_id' => $admin->id,
                    'token' => $token,
                    'response' => $response->json(),
                ]);
            }
        }
    }
}
