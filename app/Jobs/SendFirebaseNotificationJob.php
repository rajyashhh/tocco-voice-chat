<?php
namespace App\Jobs;

use App\Models\User;
use App\Helpers\Common;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendFirebaseNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $tokens,
        public string $title,
        public ?string $body = null,   // رسائل الميديا (صورة/صوت/ملصق) بلا نص → null مسموح
        public array $data = [],
        public ?string $messageType = null,
        public $user = null,
        public string $action = '',
        public string $type = '',
        public string $id = '',
        public string $notification_type = 'user_notification',
    ) {}

    public function handle()
    {
        $start = microtime(true);
        $api_access_key = Common::getPublicGoogleAccessToken();
        $projectId = env('FIREBASE_PROJECT_NAME');

        $client = new Client([
            'headers'  => [
                'Authorization' => 'Bearer ' . $api_access_key,
                'Content-Type'  => 'application/json',
            ]
        ]);

        $promises = [];

        $users =  User::select(['id', 'notification_id'])->whereIn('notification_id',$this->tokens)
            ->where('is_logout', 0)
            ->where('notification_id', '!=', null)
            ->get();

        $hasInPack = $this->user && Common::hasInPack($this->user->id, 18, true);

        foreach ($users as $user) {

            $token = $user->notification_id;


            $notification = [
                'title' => $this->title,
                'body'  => $this->body ?? '',
            ];

            $userData = [];
            if ($this->user) {

                $userData = [
                    'user_id'        => $this->user->id,
                    'name'           => $this->user->name,
                    'uuid'           => $this->user->uuid,
                    'has_color_name' => $hasInPack,
                    'image'          => $this->user->profile->avatar ?? '',
                ];
            }

            $dataPayload = [
                'click_action'       => 'FLUTTER_NOTIFICATION_CLICK',
                'message-type'       => (string) ($this->messageType ?? ''),
                'action'             => $this->action,
                'type'               => $this->type,
                'id'                 => $this->id,
                'notification_type'  => $this->notification_type,
            ];

            foreach ($this->data as $key => $value) {
                $dataPayload[$key] = is_array($value) ? json_encode($value) : (string) $value;
            }

            if (!empty($userData)) {
                $dataPayload['user'] = json_encode($userData); // ✅ user أيضاً لازم يكون نص
            }

            $payload = [
                'token'        => $token,
                'notification' => $notification,
                'data'         => $dataPayload,
            ];

            if (isset($this->data['image'])) {
                $payload['notification']['image'] = $this->data['image'];
            }

            $promises[$token] = $client->postAsync("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'json' => ['message' => $payload]
            ]);

//            $headers = [
//                'Authorization' => 'Bearer ' . $api_access_key,
//                'Content-Type'  => 'application/json',
//            ];
//
//            if (!$user->is_logout)     Http::withHeaders($headers)->post(
//                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
//                ['message' => $payload]
//           );
        }

        try {
            Utils::unwrap($promises);
        } catch (\Throwable $_) {
        }
    }

}
