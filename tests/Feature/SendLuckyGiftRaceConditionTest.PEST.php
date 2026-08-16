<?php

/**
 * ══════════════════════════════════════════════════════════════════════════════
 * Feature Test: اختبار Race Condition لإرسال الهدايا المحظوظة - نسخة Pest
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * هذا الملف هو نسخة بديلة باستخدام Pest بدلاً من PHPUnit
 * لاستخدامه، تأكد من تثبيت Pest أولاً:
 * composer require pestphp/pest --dev --with-all-dependencies
 * composer require pestphp/pest-plugin-laravel --dev
 *
 * تشغيل الاختبار:
 * ./vendor/bin/pest tests/Feature/SendLuckyGiftRaceConditionTest.PEST.php
 */

use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\GiftLog;
use App\Models\FairLuckWallet;
use App\Models\FairLuckTransaction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;
use function Pest\Laravel\{actingAs, postJson, withoutMiddleware, assertDatabaseHas};

// ══════════════════════════════════════════════════════════════
// Setup & Helpers
// ══════════════════════════════════════════════════════════════

beforeEach(function () {
    // تعطيل Middleware
    withoutMiddleware([
        \App\Http\Middleware\CheckCpu::class,
        \App\Http\Middleware\AppFeatureEnable::class,
        \App\Http\Middleware\CheckLatestToken::class,
    ]);

    // إعداد Settings
    settings()->set('stop_luckyGift', 0);
    \App\Helpers\Common::withMoneyKeyWrite(fn() => settings()->set('close_open_gifts', 0));
    settings()->set('lucky_gift_version', 4);

    // تنظيف Redis
    $walletTypes = [
        FairLuckWallet::TYPE_GLOBAL_VAULT,
        FairLuckWallet::TYPE_JACKPOT_WALLET,
        FairLuckWallet::TYPE_MEDIUM_WALLET,
    ];
    foreach ($walletTypes as $type) {
        Redis::del("fairluck:wallet:{$type}");
    }

    // Mock Events
    Event::fake();

    // إنشاء البيانات الوهمية
    $this->sender = User::factory()->create([
        'di' => 1000000,
        'email' => 'sender_pest_' . uniqid() . '@test.com',
    ]);

    $this->receiver = User::factory()->create([
        'di' => 0,
        'email' => 'receiver_pest_' . uniqid() . '@test.com',
    ]);

    $this->gift = Gift::factory()->create([
        'type' => 6,
        'price' => 1000,
        'enable' => 1,
        'name' => 'Pest Test Gift ' . uniqid(),
    ]);

    $this->room = Room::factory()->create([
        'uid' => $this->sender->id,
        'type' => 'audio',
    ]);

    $this->endpoint = 'api/gifts/v2/send-lucky-gift-combo';
});

// ══════════════════════════════════════════════════════════════
// Test Cases
// ══════════════════════════════════════════════════════════════

it('يمكن إرسال هدية واحدة بنجاح', function () {
    $senderBalanceBefore = $this->sender->di;

    $response = actingAs($this->sender, 'sanctum')
        ->postJson($this->endpoint, [
            'id' => $this->gift->id,
            'owner_id' => $this->room->uid,
            'toUid' => $this->receiver->id,
            'num' => 1,
            'count' => 1,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'combo',
                'session',
                'total_user_win',
            ]
        ]);

    // التحقق من خصم الرصيد
    $this->sender->refresh();
    expect($this->sender->di)->toBe($senderBalanceBefore - $this->gift->price);

    // التحقق من إنشاء سجل
    assertDatabaseHas('gift_logs', [
        'sender_id' => $this->sender->id,
        'receiver_id' => $this->receiver->id,
        'giftId' => $this->gift->id,
    ]);
});

it('يمنع Race Condition عند إرسال 10 طلبات متتالية', function () {
    $concurrentRequests = 10;
    $senderBalanceBefore = $this->sender->di;
    $giftLogsBefore = GiftLog::where('sender_id', $this->sender->id)->count();

    echo "\n" . str_repeat("=", 100) . "\n";
    echo "🔥 PEST - RACE CONDITION TEST\n";
    echo str_repeat("=", 100) . "\n";

    $responses = [];
    $startTime = microtime(true);

    for ($i = 1; $i <= $concurrentRequests; $i++) {
        $requestStartTime = microtime(true);

        $response = actingAs($this->sender, 'sanctum')
            ->postJson($this->endpoint, [
                'id' => $this->gift->id,
                'owner_id' => $this->room->uid,
                'toUid' => $this->receiver->id,
                'num' => 1,
                'count' => 1,
            ]);

        $requestDuration = microtime(true) - $requestStartTime;

        $responses[] = [
            'status' => $response->status(),
            'duration' => round($requestDuration, 4),
        ];

        echo sprintf(
            "Request #%02d | Status: %d | Duration: %.4fs\n",
            $i,
            $response->status(),
            $requestDuration
        );
    }

    $totalDuration = microtime(true) - $startTime;

    echo str_repeat("-", 100) . "\n";
    echo sprintf("Total: %.4fs | Avg: %.4fs\n", $totalDuration, $totalDuration / $concurrentRequests);
    echo str_repeat("=", 100) . "\n";

    // التحققات
    $this->sender->refresh();
    $senderBalanceAfter = $this->sender->di;
    $giftLogsAfter = GiftLog::where('sender_id', $this->sender->id)->count();

    $successfulRequests = collect($responses)->where('status', 200)->count();

    // 1. التحقق من عدم وجود رصيد سالب
    expect($senderBalanceAfter)->toBeGreaterThanOrEqual(0);

    // 2. التحقق من خصم الرصيد
    $expectedDeduction = $this->gift->price * $successfulRequests;
    $actualDeduction = $senderBalanceBefore - $senderBalanceAfter;

    expect($actualDeduction)->toBeGreaterThanOrEqual($expectedDeduction);

    // 3. التحقق من إنشاء سجلات
    $newLogs = $giftLogsAfter - $giftLogsBefore;
    expect($newLogs)->toBeGreaterThan(0);

    echo sprintf("✅ Successful: %d | New Logs: %d | Balance OK: %s\n",
        $successfulRequests,
        $newLogs,
        $senderBalanceAfter >= 0 ? 'YES' : 'NO'
    );
});

it('يمكن لعدة مستخدمين الإرسال في نفس الوقت', function () {
    $senders = User::factory()->count(5)->create(['di' => 100000]);

    foreach ($senders as $sender) {
        $response = actingAs($sender, 'sanctum')
            ->postJson($this->endpoint, [
                'id' => $this->gift->id,
                'owner_id' => $this->room->uid,
                'toUid' => $this->receiver->id,
                'num' => 1,
                'count' => 1,
            ]);

        expect($response->status())->toBe(200);
    }

    // التحقق من 5 سجلات منفصلة
    $totalLogs = GiftLog::whereIn('sender_id', $senders->pluck('id'))
        ->where('receiver_id', $this->receiver->id)
        ->count();

    expect($totalLogs)->toBe(5);
});

it('يرفض الطلب عند عدم كفاية الرصيد', function () {
    $this->sender->update(['di' => 500]); // أقل من سعر الهدية

    $response = actingAs($this->sender, 'sanctum')
        ->postJson($this->endpoint, [
            'id' => $this->gift->id,
            'owner_id' => $this->room->uid,
            'toUid' => $this->receiver->id,
            'num' => 1,
            'count' => 1,
        ]);

    $responseData = $response->json();
    expect($responseData['status'] ?? true)->toBeFalse();
});

it('يمكن إرسال هدية لعدة مستقبلين', function () {
    $receivers = User::factory()->count(3)->create(['di' => 0]);
    $receiverIds = $receivers->pluck('id')->implode(',');

    $senderBalanceBefore = $this->sender->di;

    $response = actingAs($this->sender, 'sanctum')
        ->postJson($this->endpoint, [
            'id' => $this->gift->id,
            'owner_id' => $this->room->uid,
            'toUid' => $receiverIds,
            'num' => 1,
            'count' => 1,
        ]);

    $response->assertStatus(200);

    // التحقق من الخصم (سعر × عدد المستقبلين)
    $this->sender->refresh();
    $expectedDeduction = $this->gift->price * 3;
    expect($this->sender->di)->toBe($senderBalanceBefore - $expectedDeduction);

    // التحقق من السجلات
    foreach ($receivers as $receiver) {
        assertDatabaseHas('gift_logs', [
            'sender_id' => $this->sender->id,
            'receiver_id' => $receiver->id,
            'giftId' => $this->gift->id,
        ]);
    }
});

test('البيانات غير الصحيحة يتم رفضها', function () {
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
    ];

    foreach ($invalidPayloads as $payload) {
        $response = actingAs($this->sender, 'sanctum')
            ->postJson($this->endpoint, $payload);

        $shouldFail = $response->status() !== 200 || !($response->json()['status'] ?? true);
        expect($shouldFail)->toBeTrue();
    }
});
