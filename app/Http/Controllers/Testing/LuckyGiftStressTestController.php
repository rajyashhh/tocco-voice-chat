<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Gift;
use App\Models\Room;
use App\Models\CoreWallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LuckyGiftStressTestController extends Controller
{
    /**
     * عرض صفحة الاختبار
     */
    public function index()
    {
        // جلب الهدايا من فئة Lucky فقط (type = 6)
        $gifts = Gift::where('type', 6)
            ->where('enable', 1)
            ->select('id', 'name', 'e_name', 'price')
            ->get();

        // جلب الغرف المتاحة
        // ملاحظة: معظم الغرف room_type = null، لذا نجلب كل الغرف
        // يمكن تصفية حسب room_status = 1 (نشطة) أو room_type إذا كانت محددة
        $rooms = Room::select('id', 'uid', 'room_name', 'room_type')
            ->limit(20)
            ->get();

        return view('testing.lucky-gift-stress-test', compact('gifts', 'rooms'));
    }

    /**
     * تنفيذ الاختبار
     */
    public function runTest(Request $request)
    {
        // زيادة الوقت المسموح
        set_time_limit(300); // 5 دقائق
        ini_set('max_execution_time', 300);

        $request->validate([
            'sender_ids' => 'required|string',
            'receiver_ids' => 'required|string',
            'gift_id' => 'required|exists:gifts,id',
            'room_id' => 'required|exists:rooms,id',
            'num' => 'required|integer|min:1|max:100',
            'count' => 'required|integer|min:1|max:10',
            'requests_per_user' => 'required|integer|min:1|max:5', // حد أقصى 5 للاختبار المباشر
            'concurrent' => 'required|boolean',
        ]);

        $senderIds = array_map('intval', array_filter(explode(',', $request->sender_ids)));
        $receiverIds = array_map('intval', array_filter(explode(',', $request->receiver_ids)));

        if (empty($senderIds) || empty($receiverIds)) {
            return response()->json(['success' => false, 'message' => 'معرفات المستخدمين غير صحيحة']);
        }

        // التحقق من وجود المستخدمين
        $senders = User::whereIn('id', $senderIds)->get();
        $receivers = User::whereIn('id', $receiverIds)->get();

        if ($senders->count() !== count($senderIds)) {
            return response()->json(['success' => false, 'message' => 'بعض المرسلين غير موجودين']);
        }

        if ($receivers->count() !== count($receiverIds)) {
            return response()->json(['success' => false, 'message' => 'بعض المستلمين غير موجودين']);
        }

        // جلب الهدية
        $gift = Gift::find($request->gift_id);
        $room = Room::find($request->room_id);

        // حساب التكلفة المتوقعة لكل طلب
        $costPerRequest = $gift->price * $request->num * count($receiverIds) * $request->count;

        // التأكد من أن المرسلين لديهم رصيد كافي
        foreach ($senders as $sender) {
            $totalCostForSender = $costPerRequest * $request->requests_per_user;
            if ($sender->di < $totalCostForSender) {
                return response()->json([
                    'success' => false,
                    'message' => "المستخدم {$sender->name} (ID: {$sender->id}) ليس لديه رصيد كافي. المطلوب: {$totalCostForSender}, المتاح: {$sender->di}"
                ]);
            }
        }

        // حفظ الأرصدة قبل الاختبار
        $beforeBalances = $this->captureBalances($senderIds, $receiverIds, $room->uid);

        // بدء الاختبار مباشرة (بدون Background Job)
        $testId = 'test_' . time() . '_' . uniqid();

        Log::channel('lucky_gift')->info('🧪 Starting Direct Stress Test', [
            'test_id' => $testId,
            'senders' => count($senderIds),
            'receivers' => count($receiverIds),
            'requests_per_user' => $request->requests_per_user,
            'concurrent' => $request->concurrent,
            'cost_per_request' => $costPerRequest,
        ]);

        $startTime = microtime(true);

        // تشغيل الاختبار مباشرة
        if ($request->concurrent) {
            $results = $this->runConcurrentTest($senders, $receiverIds, $request, $testId);
        } else {
            $results = $this->runSequentialTest($senders, $receiverIds, $request, $testId);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        Log::channel('lucky_gift')->info('🕐 انتظار معالجة الـ Jobs (gift_logs & cashback في الـ Queue)...');

        // الانتظار لإتمام Jobs في الـ Queue
        // gift_logs و user_coin_logs في Queue وممكن يتأخروا لحد 5 دقائق
        // نقرأ الأرصدة فوراً (di & monthly_diamonds تتحدث مباشرة)
        // ثم ننتظر ونقرأ الـ logs بعد فترة

        // قراءة أولى: الأرصدة والماسات (فورية)
        sleep(3); // انتظار 3 ثواني للأرصدة
        $afterBalances_immediate = $this->captureBalances($senderIds, $receiverIds, $room->uid);

        // قراءة ثانية: بعد 10 ثواني لـ gift_logs و coin_logs
        Log::channel('lucky_gift')->info('⏳ انتظار 10 ثواني إضافية للـ Queue Jobs...');
        sleep(10);
        $afterBalances = $this->captureBalances($senderIds, $receiverIds, $room->uid);

        Log::channel('lucky_gift')->info('✅ اكتمل جلب البيانات بعد الاختبار');

        // حساب القيم المطلوبة لـ analyzeResults
        $costPerRequest = $gift->price * $request->num * count($receiverIds) * $request->count;
        $giftPrice = $gift->price;
        $num = $request->num;
        $receiverCount = count($receiverIds);
        $count = $request->count;

        // تحليل النتائج
        $analysis = $this->analyzeResults(
            $beforeBalances,
            $afterBalances,
            $results,
            $costPerRequest,
            $giftPrice,
            $num,
            $receiverCount,
            $count
        );

        // حفظ التقرير
        $reportPath = $this->saveReport($testId, $beforeBalances, $afterBalances, $analysis, $results);

        // إرجاع النتائج مباشرة
        return response()->json([
            'success' => true,
            'test_id' => $testId,
            'message' => 'اكتمل الاختبار بنجاح',
            'duration' => $duration,
            'results' => $results,
            'before_balances' => $beforeBalances,
            'after_balances' => $afterBalances,
            'analysis' => $analysis,
            'report_path' => $reportPath,
        ]);
    }

    /**
     * متابعة حالة الاختبار
     */
    public function checkStatus($testId)
    {
        $progress = \Illuminate\Support\Facades\Cache::get("stress_test_{$testId}_progress");
        $results = \Illuminate\Support\Facades\Cache::get("stress_test_{$testId}_results");

        if (!$progress) {
            return response()->json([
                'success' => false,
                'message' => 'الاختبار غير موجود أو انتهت صلاحيته',
            ], 404);
        }

        $response = [
            'success' => true,
            'test_id' => $testId,
            'progress' => $progress,
        ];

        // إذا اكتمل الاختبار، أضف النتائج
        if ($progress['status'] === 'completed' && $results) {
            $beforeBalances = \Illuminate\Support\Facades\Cache::get("stress_test_{$testId}_before_balances");
            $afterBalances = $this->captureBalances(
                \Illuminate\Support\Facades\Cache::get("stress_test_{$testId}_sender_ids", []),
                \Illuminate\Support\Facades\Cache::get("stress_test_{$testId}_receiver_ids", []),
                \Illuminate\Support\Facades\Cache::get("stress_test_{$testId}_room_owner_id")
            );

            $response['results'] = $results;
            $response['before_balances'] = $beforeBalances;
            $response['after_balances'] = $afterBalances;
        }

        return response()->json($response);
    }

    /**
     * النسخة القديمة - اختبار مباشر (للاختبارات الصغيرة فقط)
     */
    public function runTestDirect(Request $request)
    {
        // زيادة الوقت المسموح
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $request->validate([
            'sender_ids' => 'required|string',
            'receiver_ids' => 'required|string',
            'gift_id' => 'required|exists:gifts,id',
            'room_id' => 'required|exists:rooms,id',
            'num' => 'required|integer|min:1|max:100',
            'count' => 'required|integer|min:1|max:10',
            'requests_per_user' => 'required|integer|min:1|max:10', // حد أقصى 10 للمباشر
            'concurrent' => 'required|boolean',
        ]);

        $senderIds = array_map('intval', array_filter(explode(',', $request->sender_ids)));
        $receiverIds = array_map('intval', array_filter(explode(',', $request->receiver_ids)));

        if (empty($senderIds) || empty($receiverIds)) {
            return response()->json(['success' => false, 'message' => 'معرفات المستخدمين غير صحيحة']);
        }

        // التحقق من وجود المستخدمين
        $senders = User::whereIn('id', $senderIds)->get();
        $receivers = User::whereIn('id', $receiverIds)->get();

        if ($senders->count() !== count($senderIds)) {
            return response()->json(['success' => false, 'message' => 'بعض المرسلين غير موجودين']);
        }

        if ($receivers->count() !== count($receiverIds)) {
            return response()->json(['success' => false, 'message' => 'بعض المستلمين غير موجودين']);
        }

        // جلب الهدية
        $gift = Gift::find($request->gift_id);
        $room = Room::find($request->room_id);

        // حساب التكلفة المتوقعة لكل طلب
        $costPerRequest = $gift->price * $request->num * count($receiverIds) * $request->count;

        // التأكد من أن المرسلين لديهم رصيد كافي
        foreach ($senders as $sender) {
            $totalCostForSender = $costPerRequest * $request->requests_per_user;
            if ($sender->di < $totalCostForSender) {
                return response()->json([
                    'success' => false,
                    'message' => "المستخدم {$sender->name} (ID: {$sender->id}) ليس لديه رصيد كافي. المطلوب: {$totalCostForSender}, المتاح: {$sender->di}"
                ]);
            }
        }

        // حفظ الأرصدة قبل الاختبار
        $beforeBalances = $this->captureBalances($senderIds, $receiverIds, $room->uid);

        // بدء الاختبار
        $testId = 'test_direct_' . time() . '_' . uniqid();

        $startTime = microtime(true);

        if ($request->concurrent) {
            $results = $this->runConcurrentTest($senders, $receiverIds, $request, $testId);
        } else {
            $results = $this->runSequentialTest($senders, $receiverIds, $request, $testId);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // الانتظار قليلاً لإتمام Jobs
        sleep(3);

        // جلب الأرصدة بعد الاختبار
        $afterBalances = $this->captureBalances($senderIds, $receiverIds, $room->uid);

        // تحليل النتائج
        $analysis = $this->analyzeResults(
            $beforeBalances,
            $afterBalances,
            $results,
            $costPerRequest,
            $gift->price,
            $request->num,
            count($receiverIds),
            $request->count
        );

        // حفظ تقرير الاختبار
        $reportPath = $this->saveReport($testId, [
            'test_config' => $request->all(),
            'duration' => $duration,
            'before_balances' => $beforeBalances,
            'after_balances' => $afterBalances,
            'results' => $results,
            'analysis' => $analysis,
        ]);

        Log::channel('lucky_gift')->info('✅ Stress Test Completed', [
            'test_id' => $testId,
            'duration' => $duration,
            'report_path' => $reportPath,
        ]);

        return response()->json([
            'success' => true,
            'test_id' => $testId,
            'duration' => $duration,
            'results' => $results,
            'analysis' => $analysis,
            'before_balances' => $beforeBalances,
            'after_balances' => $afterBalances,
            'report_path' => $reportPath,
        ]);
    }

    /**
     * اختبار متزامن باستخدام Async Requests بحجم Batch محدود
     */
    private function runConcurrentTest($senders, $receiverIds, $request, $testId)
    {
        $receiverIdsString = implode(',', $receiverIds);
        $results = [
            'total_requests' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'response_times' => [],
        ];

        $room = Room::find($request->room_id);
        $requestConfigs = [];

        // تحضير جميع الـ Requests
        foreach ($senders as $sender) {
            $token = $sender->createToken('stress-test-' . $testId)->plainTextToken;

            for ($i = 0; $i < $request->requests_per_user; $i++) {
                $results['total_requests']++;
                $requestConfigs[] = [
                    'sender_id' => $sender->id,
                    'sender_name' => $sender->name,
                    'token' => $token,
                    'request_num' => $i + 1,
                ];
            }
        }

        // تنفيذ الطلبات على دفعات (Batches) لتجنب تحميل السيرفر
        $batchSize = 10; // عدد الطلبات في كل دفعة
        $batches = array_chunk($requestConfigs, $batchSize);

        Log::channel('lucky_gift')->info('🔄 Starting Concurrent Test in Batches', [
            'total_requests' => count($requestConfigs),
            'batch_size' => $batchSize,
            'total_batches' => count($batches),
        ]);

        foreach ($batches as $batchIndex => $batch) {
            $startTime = microtime(true);

            try {
                // استخدام HTTP Pool لكل دفعة
                $responses = Http::pool(function ($pool) use ($batch, $request, $room, $receiverIdsString) {
                    return collect($batch)->mapWithKeys(function ($config, $index) use ($pool, $request, $room, $receiverIdsString) {
                        return ["req_{$index}" => $pool->as("req_{$index}")
                            ->withToken($config['token'])
                            ->timeout(90)
                            ->connectTimeout(10)
                            ->retry(2, 500) // محاولة مرتين مع تأخير 500ms
                            ->post(url('/api/gifts/v2/send-lucky-gift-combo'), [
                                'id' => $request->gift_id,
                                'owner_id' => $room->uid,
                                'room_id' => $request->room_id,
                                'toUid' => $receiverIdsString,
                                'num' => $request->num,
                                'count' => $request->count,
                            ])];
                    })->all();
                });

                // تحليل النتائج
                foreach ($responses as $key => $response) {
                    $responseTime = microtime(true) - $startTime;

                    try {
                        if ($response->successful()) {
                            $results['successful']++;
                            $results['response_times'][] = $responseTime;
                        } elseif ($response->failed()) {
                            $results['failed']++;
                            $results['errors'][] = [
                                'batch' => $batchIndex + 1,
                                'request' => $key,
                                'status' => $response->status(),
                                'body' => substr($response->body(), 0, 300),
                            ];
                        } else {
                            $results['failed']++;
                            $results['errors'][] = [
                                'batch' => $batchIndex + 1,
                                'request' => $key,
                                'error' => 'Request failed without response',
                            ];
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = [
                            'batch' => $batchIndex + 1,
                            'request' => $key,
                            'exception' => $e->getMessage(),
                        ];
                    }
                }

                // توقف قصير بين الدفعات لتخفيف الحمل
                if ($batchIndex < count($batches) - 1) {
                    usleep(500000); // 0.5 ثانية
                }

            } catch (\Exception $e) {
                Log::channel('lucky_gift')->error('❌ Batch Failed', [
                    'batch' => $batchIndex + 1,
                    'error' => $e->getMessage(),
                ]);

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

    /**
     * اختبار تسلسلي
     */
    private function runSequentialTest($senders, $receiverIds, $request, $testId)
    {
        $receiverIdsString = implode(',', $receiverIds);
        $results = [
            'total_requests' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'response_times' => [],
        ];

        foreach ($senders as $sender) {
            $token = $sender->createToken('stress-test-' . $testId)->plainTextToken;

            for ($i = 0; $i < $request->requests_per_user; $i++) {
                $results['total_requests']++;
                $startTime = microtime(true);

                try {
                    $response = Http::withToken($token)
                        ->timeout(60)
                        ->post(url('/api/v2/send-lucky-gift-combo'), [
                            'id' => $request->gift_id,
                            'owner_id' => Room::find($request->room_id)->uid,
                            'room_id' => $request->room_id,
                            'toUid' => $receiverIdsString,
                            'num' => $request->num,
                            'count' => $request->count,
                        ]);

                    $responseTime = microtime(true) - $startTime;
                    $results['response_times'][] = $responseTime;

                    if ($response->successful()) {
                        $results['successful']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'sender_id' => $sender->id,
                            'request_num' => $i + 1,
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ];
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'sender_id' => $sender->id,
                        'request_num' => $i + 1,
                        'exception' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * حفظ الأرصدة قبل/بعد الاختبار
     */
    private function captureBalances($senderIds, $receiverIds, $roomOwnerId)
    {
        $balances = [
            'timestamp' => Carbon::now()->toDateTimeString(),
            'senders' => [],
            'receivers' => [],
            'room_owner' => null,
            'core_wallets' => [],
        ];

        // أرصدة المرسلين
        $senders = User::whereIn('id', $senderIds)
            ->select('id', 'name', 'di', 'total_diamond_send', 'sender_level', 'sub_sender_level')
            ->get();

        foreach ($senders as $sender) {
            $balances['senders'][$sender->id] = [
                'name' => $sender->name,
                'di' => $sender->di,
                'total_diamond_send' => $sender->total_diamond_send ?? 0,
                'sender_level' => $sender->sender_level ?? 0,
                'sub_sender_level' => $sender->sub_sender_level ?? 0,
            ];
        }

        // أرصدة المستلمين
        $receivers = User::whereIn('id', $receiverIds)
            ->select('id', 'name', 'di', 'total_diamond_received', 'received_level', 'sub_receiver_level')
            ->get();

        foreach ($receivers as $receiver) {
            $balances['receivers'][$receiver->id] = [
                'name' => $receiver->name,
                'di' => $receiver->di,
                'total_diamond_received' => $receiver->total_diamond_received ?? 0,
                'received_level' => $receiver->received_level ?? 0,
                'sub_receiver_level' => $receiver->sub_receiver_level ?? 0,
            ];
        }

        // رصيد صاحب الغرفة
        if ($roomOwnerId) {
            $roomOwner = User::find($roomOwnerId);
            if ($roomOwner) {
                $balances['room_owner'] = [
                    'id' => $roomOwner->id,
                    'name' => $roomOwner->name,
                    'di' => $roomOwner->di,
                ];
            }
        }

        // المحافظ المركزية
        $coreWallets = CoreWallet::whereIn('name', ['app_wallet', 'owner_wallet'])->get();
        foreach ($coreWallets as $wallet) {
            $balances['core_wallets'][$wallet->name] = [
                'coins' => $wallet->coins,
            ];
        }

        // الماسات الشهرية (monthly_diamond_receives)
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthlyDiamonds = \App\Models\MonthlyDiamondReceive::whereIn('user_id', $receiverIds)
            ->where('month', $currentMonth)
            ->where('year', $currentYear)
            ->get();

        $balances['monthly_diamonds'] = [];
        foreach ($monthlyDiamonds as $record) {
            $balances['monthly_diamonds'][$record->user_id] = [
                'monthly_diamond_received' => $record->monthly_diamond_received ?? 0,
            ];
        }

        // عدد سجلات الهدايا (gift_logs) - آخر 5 دقائق
        $recentGiftLogs = \App\Models\GiftLog::where('created_at', '>=', now()->subMinutes(5))
            ->whereIn('sender_id', $senderIds)
            ->count();
        $balances['gift_logs_count'] = $recentGiftLogs;

        // عدد سجلات الكوينز (user_coin_logs) للكاش باك - آخر 5 دقائق
        $allUserIds = array_merge($senderIds, $receiverIds);
        $recentCoinLogs = \App\Models\UserCoinLog::where('created_at', '>=', now()->subMinutes(5))
            ->whereIn('user_id', $allUserIds)
            ->where('type', 'LUCKY_GIFT')
            ->count();
        $balances['coin_logs_count'] = $recentCoinLogs;

        return $balances;
    }

    /**
     * تحليل النتائج والتحقق من صحة الأرصدة
     */
    private function analyzeResults($before, $after, $results, $costPerRequest, $giftPrice, $num, $receiverCount, $count)
    {
        $analysis = [
            'balance_issues' => [],
            'discrepancies' => [],
            'summary' => [
                'total_expected_deduction' => 0,
                'total_actual_deduction' => 0,
                'total_expected_receiver_gain' => 0,
                'total_actual_receiver_gain' => 0,
            ],
            'integrity_check' => 'PASSED',
        ];

        // تحليل المرسلين
        foreach ($before['senders'] as $senderId => $beforeData) {
            $afterData = $after['senders'][$senderId] ?? null;

            if (!$afterData) {
                $analysis['balance_issues'][] = [
                    'type' => 'SENDER_MISSING',
                    'user_id' => $senderId,
                    'message' => 'بيانات المرسل مفقودة بعد الاختبار',
                ];
                $analysis['integrity_check'] = 'FAILED';
                continue;
            }

            $actualDeduction = $beforeData['di'] - $afterData['di'];
            $expectedDeduction = $costPerRequest * ($results['successful'] / count($before['senders']));

            $analysis['summary']['total_expected_deduction'] += $expectedDeduction;
            $analysis['summary']['total_actual_deduction'] += $actualDeduction;

            // التسامح في الفرق (بسبب الكاشباك)
            $tolerance = $costPerRequest * 10; // 10x tolerance for cashback

            if (abs($actualDeduction - $expectedDeduction) > $tolerance) {
                $analysis['discrepancies'][] = [
                    'type' => 'SENDER_BALANCE',
                    'user_id' => $senderId,
                    'name' => $beforeData['name'],
                    'before' => $beforeData['di'],
                    'after' => $afterData['di'],
                    'expected_deduction' => round($expectedDeduction),
                    'actual_deduction' => $actualDeduction,
                    'difference' => $actualDeduction - $expectedDeduction,
                ];
            }

            // التحقق من عدم وجود رصيد سالب
            if ($afterData['di'] < 0) {
                $analysis['balance_issues'][] = [
                    'type' => 'NEGATIVE_BALANCE',
                    'user_id' => $senderId,
                    'name' => $beforeData['name'],
                    'balance' => $afterData['di'],
                    'severity' => 'CRITICAL',
                ];
                $analysis['integrity_check'] = 'FAILED';
            }
        }

        // ✨ تحليل الكاش باك للمرسلين (المرسل رصيده بيزيد من الكاش باك أثناء الإرسال)
        $analysis['sender_cashback_analysis'] = [];
        foreach ($before['senders'] as $senderId => $beforeData) {
            $afterData = $after['senders'][$senderId] ?? null;
            if (!$afterData) continue;

            $actualChange = $afterData['di'] - $beforeData['di'];
            $expectedDeduction = $costPerRequest * ($results['successful'] / count($before['senders']));

            // الكاش باك = الفرق بين الخصم المتوقع والفعلي (إذا كان الخصم الفعلي أقل)
            $estimatedCashback = $expectedDeduction - ($beforeData['di'] - $afterData['di']);

            $analysis['sender_cashback_analysis'][$senderId] = [
                'name' => $beforeData['name'],
                'balance_before' => $beforeData['di'],
                'balance_after' => $afterData['di'],
                'expected_cost' => round($expectedDeduction),
                'actual_change' => $actualChange,
                'estimated_cashback' => round($estimatedCashback > 0 ? $estimatedCashback : 0),
                'note' => $estimatedCashback > 0 ? 'حصل على كاش باك' : 'لم يحصل على كاش باك',
            ];
        }

        // تحليل المستلمين
        $receiverFeeRate = \App\Models\FairLuckSetting::getReceiverFeeRate();
        $expectedReceiverGainPerRequest = $giftPrice * $num * $receiverFeeRate * $count;

        foreach ($before['receivers'] as $receiverId => $beforeData) {
            $afterData = $after['receivers'][$receiverId] ?? null;

            if (!$afterData) {
                $analysis['balance_issues'][] = [
                    'type' => 'RECEIVER_MISSING',
                    'user_id' => $receiverId,
                    'message' => 'بيانات المستلم مفقودة بعد الاختبار',
                ];
                $analysis['integrity_check'] = 'FAILED';
                continue;
            }

            // ⚠️ تحقق: هل المستلم هو نفسه مرسل؟
            $isAlsoSender = isset($before['senders'][$receiverId]);

            $actualGain = $afterData['di'] - $beforeData['di'];
            $expectedGain = $expectedReceiverGainPerRequest * $results['successful'];

            $analysis['summary']['total_expected_receiver_gain'] += $expectedGain;
            $analysis['summary']['total_actual_receiver_gain'] += $actualGain;

            // إذا كان المستلم هو نفسه مرسل، التحليل يكون مختلف
            if ($isAlsoSender) {
                // الكاش باك موجود في sender_cashback_analysis
                // التغيير الفعلي = تكلفة الإرسال - الكاش باك + مكسب الاستقبال
                $senderCashback = $analysis['sender_cashback_analysis'][$receiverId]['estimated_cashback'] ?? 0;

                // نضيف ملاحظة للتوضيح
                $analysis['sender_cashback_analysis'][$receiverId]['is_also_receiver'] = true;
                $analysis['sender_cashback_analysis'][$receiverId]['expected_receiver_gain'] = round($expectedGain);
                $analysis['sender_cashback_analysis'][$receiverId]['note'] = 'مرسل ومستلم - الرصيد النهائي = (التكلفة - الكاش باك + مكسب الاستقبال)';

                // لا نضيف discrepancy لأن الحساب صحيح
                continue;
            }

            // التسامح 5%
            $tolerance = $expectedGain * 0.05;

            if (abs($actualGain - $expectedGain) > $tolerance && $expectedGain > 0) {
                $analysis['discrepancies'][] = [
                    'type' => 'RECEIVER_BALANCE',
                    'user_id' => $receiverId,
                    'name' => $beforeData['name'],
                    'before' => $beforeData['di'],
                    'after' => $afterData['di'],
                    'expected_gain' => round($expectedGain),
                    'actual_gain' => $actualGain,
                    'difference' => $actualGain - $expectedGain,
                ];
            }
        }

        // التحقق من صحة المحافظ المركزية
        if (isset($before['core_wallets']['app_wallet']) && isset($after['core_wallets']['app_wallet'])) {
            $appWalletChange = $after['core_wallets']['app_wallet']['coins'] - $before['core_wallets']['app_wallet']['coins'];

            $analysis['summary']['app_wallet_change'] = $appWalletChange;
        }

        if (isset($before['core_wallets']['owner_wallet']) && isset($after['core_wallets']['owner_wallet'])) {
            $ownerWalletChange = $after['core_wallets']['owner_wallet']['coins'] - $before['core_wallets']['owner_wallet']['coins'];

            $analysis['summary']['owner_wallet_change'] = $ownerWalletChange;
        }

        // حساب متوسط زمن الاستجابة
        if (!empty($results['response_times'])) {
            $analysis['performance'] = [
                'avg_response_time' => round(array_sum($results['response_times']) / count($results['response_times']), 3),
                'min_response_time' => round(min($results['response_times']), 3),
                'max_response_time' => round(max($results['response_times']), 3),
            ];
        }

        // ✨ تحليل الماسات الشهرية (monthly_diamond_receives)
        $analysis['monthly_diamonds_analysis'] = [];
        foreach ($before['receivers'] as $receiverId => $beforeData) {
            $beforeMonthly = $before['monthly_diamonds'][$receiverId]['monthly_diamond_received'] ?? 0;
            $afterMonthly = $after['monthly_diamonds'][$receiverId]['monthly_diamond_received'] ?? 0;
            $monthlyIncrease = $afterMonthly - $beforeMonthly;

            $analysis['monthly_diamonds_analysis'][$receiverId] = [
                'name' => $beforeData['name'],
                'before' => $beforeMonthly,
                'after' => $afterMonthly,
                'increase' => $monthlyIncrease,
            ];

            // التحقق من وجود فرق
            if ($monthlyIncrease > 0) {
                $analysis['summary']['total_monthly_diamonds_increased'] = ($analysis['summary']['total_monthly_diamonds_increased'] ?? 0) + $monthlyIncrease;
            }
        }

        // ✨ تحليل سجلات الهدايا (gift_logs)
        $giftLogsIncrease = ($after['gift_logs_count'] ?? 0) - ($before['gift_logs_count'] ?? 0);
        $analysis['gift_logs_analysis'] = [
            'before_count' => $before['gift_logs_count'] ?? 0,
            'after_count' => $after['gift_logs_count'] ?? 0,
            'new_records' => $giftLogsIncrease,
            'expected_records' => $results['successful'], // كل طلب ناجح = سجل هدية
            'match' => $giftLogsIncrease === $results['successful'] ? 'MATCHED ✓' : 'MISMATCH ✗',
            'warning' => $giftLogsIncrease < $results['successful'] ? 'gift_logs في Queue - قد يتأخر حتى 5 دقائق' : null,
        ];

        if ($giftLogsIncrease !== $results['successful']) {
            $analysis['discrepancies'][] = [
                'type' => 'GIFT_LOGS_COUNT',
                'expected' => $results['successful'],
                'actual' => $giftLogsIncrease,
                'difference' => $giftLogsIncrease - $results['successful'],
                'message' => 'عدد سجلات الهدايا لا يطابق عدد الطلبات الناجحة',
            ];
            $analysis['integrity_check'] = 'WARNING';
        }

        // ✨ تحليل سجلات الكوينز للكاش باك (user_coin_logs)
        $coinLogsIncrease = ($after['coin_logs_count'] ?? 0) - ($before['coin_logs_count'] ?? 0);
        $analysis['coin_logs_analysis'] = [
            'before_count' => $before['coin_logs_count'] ?? 0,
            'after_count' => $after['coin_logs_count'] ?? 0,
            'new_cashback_records' => $coinLogsIncrease,
            'note' => 'عدد سجلات الكاش باك (LUCKY_GIFT type)',
            'warning' => 'الكاش باك يُعالج في Queue - قد يتأخر حسب طريقة المعالجة',
        ];

        // إضافة ملخص شامل
        $analysis['summary']['database_integrity'] = [
            'gift_logs_matched' => $giftLogsIncrease === $results['successful'],
            'monthly_diamonds_updated' => ($analysis['summary']['total_monthly_diamonds_increased'] ?? 0) > 0,
            'cashback_records_created' => $coinLogsIncrease > 0,
        ];

        return $analysis;
    }

    /**
     * حفظ التقرير في ملف
     */
    private function saveReport($testId, $data)
    {
        $reportDir = storage_path('app/stress-test-reports');

        if (!file_exists($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $filename = "{$testId}_" . Carbon::now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = "{$reportDir}/{$filename}";

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filepath;
    }

    /**
     * عرض التقارير السابقة
     */
    public function reports()
    {
        $reportDir = storage_path('app/stress-test-reports');

        if (!file_exists($reportDir)) {
            return view('testing.stress-test-reports', ['reports' => []]);
        }

        $files = array_diff(scandir($reportDir, SCANDIR_SORT_DESCENDING), ['.', '..']);
        $reports = [];

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $content = json_decode(file_get_contents("{$reportDir}/{$file}"), true);
                $reports[] = [
                    'filename' => $file,
                    'test_id' => $content['analysis']['summary'] ?? 'N/A',
                    'duration' => $content['duration'] ?? 0,
                    'integrity' => $content['analysis']['integrity_check'] ?? 'UNKNOWN',
                    'created_at' => filectime("{$reportDir}/{$file}"),
                ];
            }
        }

        return view('testing.stress-test-reports', compact('reports'));
    }

    /**
     * عرض تقرير محدد
     */
    public function viewReport($filename)
    {
        $filepath = storage_path("app/stress-test-reports/{$filename}");

        if (!file_exists($filepath)) {
            abort(404, 'Report not found');
        }

        $report = json_decode(file_get_contents($filepath), true);

        return view('testing.stress-test-report-view', compact('report', 'filename'));
    }
}
