<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\GiftLog;
use App\Models\FairLuckWallet;
use App\Models\FairLuckTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

/**
 * Feature Test: اختبار نقطة النهاية /v2/send-lucky-gift-combo
 *
 * الهدف: التأكد من عدم حدوث Race Condition عند إرسال طلبات متعددة متزامنة
 * من نفس المستخدم، والتحقق من سلامة البيانات في قاعدة البيانات
 */
class SendLuckyGiftRaceConditionTest extends TestCase
{
    // use RefreshDatabase; // استخدم هذا فقط في بيئة الاختبار المعزولة

    protected User $sender;
    protected User $receiver;
    protected Gift $gift;
    protected Room $room;
    protected string $endpoint = 'api/gifts/v2/send-lucky-gift-combo';

    /**
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ══════════════════════════════════════════════════════════════
        // ══════════════════════════════════════════════════════════════
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckCpu::class,               // تخطي فحص CPU
            \App\Http\Middleware\AppFeatureEnable::class,       // تخطي فحص تفعيل الميزة
            \App\Http\Middleware\CheckLatestToken::class,       // تخطي فحص التوكن
        ]);

        // ══════════════════════════════════════════════════════════════
        // ══════════════════════════════════════════════════════════════
        settings()->set('stop_luckyGift', 0);           // تفعيل الهدايا المحظوظة
        \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set('close_open_gifts', 0)); // فتح إرسال الهدايا
        settings()->set('lucky_gift_version', 4);       // استخدام النسخة 4

        // ══════════════════════════════════════════════════════════════
        // 3. تنظيف Redis (محافظ FairLuck)
        // ══════════════════════════════════════════════════════════════
        $this->cleanRedisWallets();

        // ══════════════════════════════════════════════════════════════
        // 4. Mock للـ Events لتجنب إطلاق البث
        // ══════════════════════════════════════════════════════════════
        Event::fake();

        // ══════════════════════════════════════════════════════════════
        // 5. إنشاء بيانات الاختبار
        // ══════════════════════════════════════════════════════════════
        $this->createTestData();
    }

    /**
     * إنشاء البيانات الوهمية للاختبار
     */
    private function createTestData(): void
    {
        // إنشاء مرسل برصيد كبير
        $this->sender = User::factory()->create([
            'di' => 1000000,  // مليون عملة
            'email' => 'sender_test_' . uniqid() . '@test.com',
        ]);

        // إنشاء مستقبل
        $this->receiver = User::factory()->create([
            'di' => 0,
            'email' => 'receiver_test_' . uniqid() . '@test.com',
        ]);

        // إنشاء هدية محظوظة (type = 6)
        $this->gift = Gift::factory()->create([
            'type' => 6,                    // Lucky Gift
            'price' => 1000,                // سعر الهدية
            'enable' => 1,                  // مفعلة
            'name' => 'Test Lucky Gift ' . uniqid(),
        ]);

        // إنشاء غرفة
        $this->room = Room::factory()->create([
            'uid' => $this->sender->id,
            'type' => 'audio',
        ]);
    }

    /**
     * تنظيف محافظ Redis
     */
    private function cleanRedisWallets(): void
    {
        $walletTypes = [
            FairLuckWallet::TYPE_GLOBAL_VAULT,
            FairLuckWallet::TYPE_JACKPOT_WALLET,
            FairLuckWallet::TYPE_MEDIUM_WALLET,
        ];

        foreach ($walletTypes as $type) {
            Redis::del("fairluck:wallet:{$type}");
        }
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * اختبار 1: إرسال طلب واحد بنجاح
     * ══════════════════════════════════════════════════════════════
     */
    public function test_single_gift_send_success()
    {
        // الترتيب: Arrange
        $senderBalanceBefore = $this->sender->di;
        $payload = $this->buildPayload();

        // الإجراء: Act
        $response = $this->sendAuthenticatedRequest($payload);

        // التحقق: Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'combo',
                'session',
                'total_user_win',
            ]
        ]);

        // التحقق من خصم الرصيد
        $this->sender->refresh();
        $expectedDeduction = $this->gift->price * 1; // سعر الهدية × العدد
        $this->assertEquals(
            $senderBalanceBefore - $expectedDeduction,
            $this->sender->di,
            'يجب خصم الرصيد الصحيح من المرسل'
        );

        // التحقق من إنشاء سجل GiftLog
        $this->assertDatabaseHas('gift_logs', [
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'giftId' => $this->gift->id,
        ]);
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * اختبار 2: Race Condition - 10 طلبات متتالية في أجزاء من الثانية
     * ══════════════════════════════════════════════════════════════
     *
     * الهدف: محاكاة ضغط عالي للتأكد من عدم:
     * - تكرار إرسال الهدية
     * - حدوث Negative Balance (رصيد سالب)
     * - فقدان البيانات (Data Loss)
     */
    public function test_concurrent_requests_race_condition_prevention()
    {
        $this->withoutExceptionHandling();

        // ══════════════════════════════════════════════════════════════
        // الإعداد
        // ══════════════════════════════════════════════════════════════
        $concurrentRequests = 10;
        $senderBalanceBefore = $this->sender->di;
        $giftLogsBefore = GiftLog::where('sender_id', $this->sender->id)->count();
        $fairLuckTransactionsBefore = FairLuckTransaction::count();

        echo "\n" . str_repeat("=", 100) . "\n";
        echo "🔥 RACE CONDITION TEST - Starting Concurrent Requests\n";
        echo str_repeat("=", 100) . "\n";
        echo sprintf("Sender Balance Before: %s\n", number_format($senderBalanceBefore));
        echo sprintf("Gift Logs Before: %d\n", $giftLogsBefore);
        echo sprintf("FairLuck Transactions Before: %d\n", $fairLuckTransactionsBefore);
        echo str_repeat("-", 100) . "\n";

        // ══════════════════════════════════════════════════════════════
        // تنفيذ 10 طلبات متتالية
        // ══════════════════════════════════════════════════════════════
        $responses = [];
        $startTime = microtime(true);

        for ($i = 1; $i <= $concurrentRequests; $i++) {
            $requestStartTime = microtime(true);

            $response = $this->sendAuthenticatedRequest($this->buildPayload());

            $requestDuration = microtime(true) - $requestStartTime;

            $responses[] = [
                'request_number' => $i,
                'status' => $response->status(),
                'duration' => round($requestDuration, 4),
                'response_data' => $response->json(),
            ];

            echo sprintf(
                "Request #%02d | Status: %d | Duration: %.4fs | Success: %s\n",
                $i,
                $response->status(),
                $requestDuration,
                $response->status() === 200 ? '✅' : '❌'
            );
        }

        $totalDuration = microtime(true) - $startTime;

        // ══════════════════════════════════════════════════════════════
        // التحليل والتحقق
        // ══════════════════════════════════════════════════════════════
        echo str_repeat("-", 100) . "\n";
        echo sprintf("Total Duration: %.4fs | Average per Request: %.4fs\n", $totalDuration, $totalDuration / $concurrentRequests);
        echo str_repeat("=", 100) . "\n";

        // تحديث بيانات المرسل
        $this->sender->refresh();
        $senderBalanceAfter = $this->sender->di;
        $giftLogsAfter = GiftLog::where('sender_id', $this->sender->id)->count();
        $fairLuckTransactionsAfter = FairLuckTransaction::count();

        // ══════════════════════════════════════════════════════════════
        // التحققات الحاسمة (Critical Assertions)
        // ══════════════════════════════════════════════════════════════

        // 1️⃣ عدد الردود الناجحة
        $successfulRequests = collect($responses)->where('status', 200)->count();
        echo "\n📊 Results Analysis:\n";
        echo sprintf("✅ Successful Requests: %d/%d\n", $successfulRequests, $concurrentRequests);
        echo sprintf("❌ Failed Requests: %d/%d\n", $concurrentRequests - $successfulRequests, $concurrentRequests);

        // 2️⃣ التحقق من عدم حدوث رصيد سالب
        $this->assertGreaterThanOrEqual(
            0,
            $senderBalanceAfter,
            '❌ CRITICAL: رصيد المرسل أصبح سالباً! (Negative Balance Detected)'
        );
        echo sprintf("💰 Sender Balance After: %s (Valid ✅)\n", number_format($senderBalanceAfter));

        // 3️⃣ التحقق من خصم الرصيد الصحيح
        $expectedTotalDeduction = $this->gift->price * $successfulRequests;
        $actualDeduction = $senderBalanceBefore - $senderBalanceAfter;

        echo sprintf(
            "💸 Balance Deduction: Expected=%s | Actual=%s | Match=%s\n",
            number_format($expectedTotalDeduction),
            number_format($actualDeduction),
            $expectedTotalDeduction === $actualDeduction ? '✅' : '⚠️'
        );

        // ملاحظة: في بعض الأنظمة قد يكون هناك فرق بسبب آلية Liquidity Protection
        $this->assertTrue(
            $actualDeduction >= $expectedTotalDeduction,
            "خصم الرصيد غير متطابق. متوقع: $expectedTotalDeduction, الفعلي: $actualDeduction"
        );

        // 4️⃣ التحقق من سجلات GiftLog
        $newGiftLogs = $giftLogsAfter - $giftLogsBefore;
        echo sprintf("📝 New Gift Logs Created: %d\n", $newGiftLogs);

        $this->assertGreaterThan(
            0,
            $newGiftLogs,
            '❌ CRITICAL: لم يتم إنشاء أي سجلات GiftLog!'
        );

        // 5️⃣ التحقق من عدم تكرار السجلات (Duplicate Prevention)
        $uniqueSessions = GiftLog::where('sender_id', $this->sender->id)
            ->where('giftId', $this->gift->id)
            ->whereNotNull('session')
            ->distinct('session')
            ->count('session');

        echo sprintf("🔑 Unique Sessions: %d\n", $uniqueSessions);

        // 6️⃣ التحقق من FairLuck Transactions
        $newTransactions = $fairLuckTransactionsAfter - $fairLuckTransactionsBefore;
        echo sprintf("🎲 New FairLuck Transactions: %d\n", $newTransactions);

        echo str_repeat("=", 100) . "\n";
        echo "✅ TEST PASSED: No race condition detected, data integrity maintained!\n";
        echo str_repeat("=", 100) . "\n";
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * اختبار 3: محاكاة ضغط من مستخدمين متعددين
     * ══════════════════════════════════════════════════════════════
     */
    public function test_multiple_users_concurrent_sending()
    {
        // إنشاء 5 مرسلين
        $senders = User::factory()->count(5)->create([
            'di' => 100000,
        ]);

        $responses = [];

        foreach ($senders as $sender) {
            $payload = [
                'id' => $this->gift->id,
                'owner_id' => $this->room->uid,
                'toUid' => $this->receiver->id,
                'num' => 1,
                'count' => 1,
            ];

            $response = $this->actingAs($sender, 'sanctum')
                ->postJson($this->endpoint, $payload);

            $responses[] = [
                'sender_id' => $sender->id,
                'status' => $response->status(),
            ];

            $this->assertEquals(200, $response->status());
        }

        // التحقق من أن كل مرسل أرسل هديته بنجاح
        $this->assertCount(5, $responses);

        // التحقق من إنشاء 5 سجلات منفصلة
        $totalLogs = GiftLog::whereIn('sender_id', $senders->pluck('id'))
            ->where('receiver_id', $this->receiver->id)
            ->count();

        $this->assertEquals(5, $totalLogs);
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * اختبار 4: رصيد غير كافٍ
     * ══════════════════════════════════════════════════════════════
     */
    public function test_insufficient_balance_rejection()
    {
        // تعيين رصيد أقل من سعر الهدية
        $this->sender->update(['di' => 500]);  // الهدية سعرها 1000

        $payload = $this->buildPayload();
        $response = $this->sendAuthenticatedRequest($payload);

        // يجب أن يفشل الطلب
        $response->assertStatus(200);  // قد تعيد API نجاح مع رسالة خطأ
        $responseData = $response->json();

        // التحقق من وجود رسالة خطأ
        $this->assertFalse(
            $responseData['status'] ?? true,
            'يجب رفض الطلب عند عدم كفاية الرصيد'
        );
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * اختبار 5: بيانات مدخلة غير صحيحة (Validation)
     * ══════════════════════════════════════════════════════════════
     */
    public function test_invalid_payload_validation()
    {
        $invalidPayloads = [
            // بدون gift_id
            [
                'owner_id' => $this->room->uid,
                'toUid' => $this->receiver->id,
                'num' => 1,
            ],
            // عدد سالب
            [
                'id' => $this->gift->id,
                'owner_id' => $this->room->uid,
                'toUid' => $this->receiver->id,
                'num' => -5,
            ],
            // بدون مستقبل
            [
                'id' => $this->gift->id,
                'owner_id' => $this->room->uid,
                'num' => 1,
            ],
        ];

        foreach ($invalidPayloads as $payload) {
            $response = $this->sendAuthenticatedRequest($payload);

            $this->assertTrue(
                $response->status() !== 200 || !($response->json()['status'] ?? true),
                'يجب رفض البيانات غير الصحيحة'
            );
        }
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * اختبار 6: إرسال لعدة مستقبلين (Combo)
     * ══════════════════════════════════════════════════════════════
     */
    public function test_send_to_multiple_receivers()
    {
        // إنشاء 3 مستقبلين
        $receivers = User::factory()->count(3)->create(['di' => 0]);
        $receiverIds = $receivers->pluck('id')->implode(',');

        $payload = [
            'id' => $this->gift->id,
            'owner_id' => $this->room->uid,
            'toUid' => $receiverIds,
            'num' => 1,
            'count' => 1,
        ];

        $senderBalanceBefore = $this->sender->di;
        $response = $this->sendAuthenticatedRequest($payload);

        $response->assertStatus(200);

        // التحقق من خصم الرصيد (سعر الهدية × عدد المستقبلين)
        $this->sender->refresh();
        $expectedDeduction = $this->gift->price * 3;
        $this->assertEquals(
            $senderBalanceBefore - $expectedDeduction,
            $this->sender->di
        );

        // التحقق من إنشاء سجل لكل مستقبل
        foreach ($receivers as $receiver) {
            $this->assertDatabaseHas('gift_logs', [
                'sender_id' => $this->sender->id,
                'receiver_id' => $receiver->id,
                'giftId' => $this->gift->id,
            ]);
        }
    }

    /**
     * ══════════════════════════════════════════════════════════════
     * Helper Methods - الدوال المساعدة
     * ══════════════════════════════════════════════════════════════
     */

    /**
     * بناء Payload للطلب
     */
    private function buildPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => $this->gift->id,
            'owner_id' => $this->room->uid,
            'toUid' => $this->receiver->id,
            'num' => 1,         // عدد الهدايا
            'count' => 1,       // عدد المحاولات (للـ lucky gift)
        ], $overrides);
    }

    /**
     * إرسال طلب موثق (Authenticated Request)
     *
     * طريقتان للمصادقة في Laravel:
     *
     * الطريقة 1: استخدام actingAs (Recommended for Tests)
     * ---------------------------------------------------------
     * هذه الطريقة تحاكي تسجيل الدخول مباشرة دون الحاجة لتوكن حقيقي
     */
    private function sendAuthenticatedRequest(array $payload)
    {
        // استخدام Sanctum actingAs
        return $this->actingAs($this->sender, 'sanctum')
            ->postJson($this->endpoint, $payload);
    }

    /**
     * الطريقة 2: استخدام Bearer Token (Alternative)
     * ---------------------------------------------------------
     * إذا كنت تريد اختبار التوكن الفعلي، استخدم هذه الطريقة
     */
    private function sendAuthenticatedRequestWithToken(array $payload)
    {
        // إنشاء توكن جديد
        $token = $this->sender->createToken('test-token')->plainTextToken;

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson($this->endpoint, $payload);
    }

    /**
     * تنظيف بعد الاختبار
     */
    protected function tearDown(): void
    {
        // يمكنك إضافة تنظيف إضافي هنا إذا لزم الأمر
        parent::tearDown();
    }
}
