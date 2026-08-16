<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Gift;
use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RunStressTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 دقائق
    public $tries = 1;

    protected $testConfig;
    protected $testId;

    public function __construct(array $testConfig, string $testId)
    {
        $this->testConfig = $testConfig;
        $this->testId = $testId;
    }

    public function handle()
    {
        Log::channel('lucky_gift')->info('🚀 Starting Background Stress Test', [
            'test_id' => $this->testId,
            'config' => $this->testConfig,
        ]);

        try {
            // تحديث الحالة: بدأ
            $this->updateProgress(0, 'starting', 'جاري تحضير الاختبار...');

            $senderIds = $this->testConfig['sender_ids'];
            $receiverIds = $this->testConfig['receiver_ids'];
            $receiverIdsString = implode(',', $receiverIds);

            // جلب البيانات
            $senders = User::whereIn('id', $senderIds)->get();
            $room = Room::find($this->testConfig['room_id']);

            $results = [
                'total_requests' => 0,
                'successful' => 0,
                'failed' => 0,
                'errors' => [],
                'response_times' => [],
            ];

            // تحضير جميع الطلبات
            $requestConfigs = [];
            foreach ($senders as $sender) {
                $token = $sender->createToken('stress-test-' . $this->testId)->plainTextToken;

                for ($i = 0; $i < $this->testConfig['requests_per_user']; $i++) {
                    $results['total_requests']++;
                    $requestConfigs[] = [
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'token' => $token,
                        'request_num' => $i + 1,
                    ];
                }
            }

            $totalRequests = count($requestConfigs);
            $this->updateProgress(10, 'running', "جاري إرسال {$totalRequests} طلب...");

            // تنفيذ الطلبات
            if ($this->testConfig['concurrent']) {
                $results = $this->runConcurrent($requestConfigs, $room, $receiverIdsString, $totalRequests);
            } else {
                $results = $this->runSequential($requestConfigs, $room, $receiverIdsString, $totalRequests);
            }

            $this->updateProgress(90, 'analyzing', 'جاري تحليل النتائج...');

            // حفظ النتائج في Cache
            Cache::put("stress_test_{$this->testId}_results", $results, 3600);

            $this->updateProgress(100, 'completed', 'اكتمل الاختبار بنجاح!');

            Log::channel('lucky_gift')->info('✅ Background Stress Test Completed', [
                'test_id' => $this->testId,
                'successful' => $results['successful'],
                'failed' => $results['failed'],
            ]);

        } catch (\Exception $e) {
            Log::channel('lucky_gift')->error('❌ Background Stress Test Failed', [
                'test_id' => $this->testId,
                'error' => $e->getMessage(),
            ]);

            $this->updateProgress(100, 'failed', 'فشل الاختبار: ' . $e->getMessage());
        }
    }

    private function runConcurrent($requestConfigs, $room, $receiverIdsString, $totalRequests)
    {
        $results = [
            'total_requests' => $totalRequests,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'response_times' => [],
        ];

        $batchSize = 10;
        $batches = array_chunk($requestConfigs, $batchSize);
        $completed = 0;

        foreach ($batches as $batchIndex => $batch) {
            $startTime = microtime(true);

            try {
                $responses = Http::pool(function ($pool) use ($batch, $room, $receiverIdsString) {
                    return collect($batch)->mapWithKeys(function ($config, $index) use ($pool, $room, $receiverIdsString) {
                        return ["req_{$index}" => $pool->as("req_{$index}")
                            ->withToken($config['token'])
                            ->timeout(90)
                            ->connectTimeout(10)
                            ->retry(2, 500)
                            ->post(url('/api/v2/send-lucky-gift-combo'), [
                                'id' => $this->testConfig['gift_id'],
                                'owner_id' => $room->uid,
                                'room_id' => $this->testConfig['room_id'],
                                'toUid' => $receiverIdsString,
                                'num' => $this->testConfig['num'],
                                'count' => $this->testConfig['count'],
                            ])];
                    })->all();
                });

                foreach ($responses as $key => $response) {
                    $responseTime = microtime(true) - $startTime;

                    if ($response->successful()) {
                        $results['successful']++;
                        $results['response_times'][] = $responseTime;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'batch' => $batchIndex + 1,
                            'request' => $key,
                            'status' => $response->status(),
                            'body' => substr($response->body(), 0, 200),
                        ];
                    }

                    $completed++;
                    $progress = 10 + (80 * ($completed / $totalRequests));
                    $this->updateProgress($progress, 'running', "تم {$completed} من {$totalRequests} طلب");
                }

                if ($batchIndex < count($batches) - 1) {
                    usleep(500000); // 0.5 ثانية
                }

            } catch (\Exception $e) {
                foreach ($batch as $config) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'batch' => $batchIndex + 1,
                        'sender_id' => $config['sender_id'],
                        'exception' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    private function runSequential($requestConfigs, $room, $receiverIdsString, $totalRequests)
    {
        $results = [
            'total_requests' => $totalRequests,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'response_times' => [],
        ];

        $completed = 0;

        foreach ($requestConfigs as $index => $config) {
            $startTime = microtime(true);

            try {
                $response = Http::withToken($config['token'])
                    ->timeout(90)
                    ->post(url('/api/v2/send-lucky-gift-combo'), [
                        'id' => $this->testConfig['gift_id'],
                        'owner_id' => $room->uid,
                        'room_id' => $this->testConfig['room_id'],
                        'toUid' => $receiverIdsString,
                        'num' => $this->testConfig['num'],
                        'count' => $this->testConfig['count'],
                    ]);

                $responseTime = microtime(true) - $startTime;
                $results['response_times'][] = $responseTime;

                if ($response->successful()) {
                    $results['successful']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'request' => $index + 1,
                        'sender_id' => $config['sender_id'],
                        'status' => $response->status(),
                        'body' => substr($response->body(), 0, 200),
                    ];
                }

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'request' => $index + 1,
                    'sender_id' => $config['sender_id'],
                    'exception' => $e->getMessage(),
                ];
            }

            $completed++;
            $progress = 10 + (80 * ($completed / $totalRequests));
            $this->updateProgress($progress, 'running', "تم {$completed} من {$totalRequests} طلب");
        }

        return $results;
    }

    private function updateProgress($percentage, $status, $message)
    {
        Cache::put("stress_test_{$this->testId}_progress", [
            'percentage' => round($percentage),
            'status' => $status,
            'message' => $message,
            'updated_at' => Carbon::now()->toDateTimeString(),
        ], 3600);
    }
}
