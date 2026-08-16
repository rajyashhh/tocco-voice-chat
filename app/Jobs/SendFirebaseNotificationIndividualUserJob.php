<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use App\Models\User;
use GuzzleHttp\Client;
use App\Helpers\Common;
use GuzzleHttp\Promise\Utils;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendFirebaseNotificationIndividualUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $tokens,
        // public string $title,
        // public string $body,
        public array $data = [],
        public ?string $messageType = null,
        public $user = null,
        public string $action = '',
        public string $type = '',
        public string $id = '',
        public string $notification_type = 'user_notification',
        public ?int $min = null,
        public ?int $max = null,
        public ?string $dataType = null,


    ) {}

    public function handle()
    {
        $title = __('congratulations');
        $start = microtime(true);
        $api_access_key = Common::getPublicGoogleAccessToken();
        $projectId = env('FIREBASE_PROJECT_NAME');

        $client = new Client([
            'headers'  => [
                'Authorization' => 'Bearer ' . $api_access_key,
                'Content-Type' => 'application/json',
            ]
        ]);

        $users = User::select(['id', 'notification_id', 'lan'])
            ->whereIn('notification_id', $this->tokens)
            ->where('is_logout', 0)
            ->whereNotNull('notification_id')
            ->get();

        if (!$users->count()) {
            return;
        }

        $hasInPack = $this->user && Common::hasInPack($this->user->id, 18, true);
        $minLevel = $this->min ?? 1;
        $maxLevel = $this->max ?? $minLevel;

        foreach ($users as $index => $user) {
            $currentLevel = $minLevel + $index;
            if ($currentLevel > $maxLevel) {
                break;
            }

            $lang = $user->lan ?? 'en';
            $body = __('api.rankingRewardLevel', ['level' => $currentLevel], $lang);

            Common::sendOfficialMessage(
                $user->id,
                $title,
                $body,
                image: $this->dataType,
            );

            $token = $user->notification_id;

            $notification = [
                'title' => $title,
                'body' => $body,
            ];

            $userData = [];
            if ($this->user) {
                $userData = [
                    'user_id' => $this->user->id,
                    'name' => $this->user->name,
                    'uuid' => $this->user->uuid,
                    'has_color_name' => $hasInPack,
                    'image' => $this->user->profile->avatar ?? '',
                ];
            }

            $dataPayload = [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'message-type' => (string) ($this->messageType ?? ''),
                'action' => $this->action,
                'type' => $this->type,
                'id' => $this->id,
                'notification_type' => $this->notification_type,
            ];

            foreach ($this->data as $key => $value) {
                $dataPayload[$key] = is_array($value) ? json_encode($value) : (string) $value;
            }

            if (!empty($userData)) {
                $dataPayload['user'] = json_encode($userData);
            }
            $imageUrl = $this->data['image'] ?? null;
            $payload = [
                'token' => $token,
                'notification' => $notification,
                'data' => $dataPayload,
                'android' => [
                    'notification' => [
                        'image' => $imageUrl
                    ]
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'mutable-content' => 1
                        ]
                    ],
                    'fcm_options' => [
                        'image' => $imageUrl
                    ]
                ],
            ];

            if (isset($this->data['image'])) {
                $payload['notification']['image'] = $this->data['image'];
            }

            $promises[$token] = $client->postAsync(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                ['json' => ['message' => $payload]]
            );
        }

        try {
            Utils::unwrap($promises);
        } catch (\Throwable $e) {
            Log::error('Error sending Firebase notifications', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
