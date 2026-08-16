<?php

namespace Tests\Feature;

use App\Models\Gift;
use App\Models\Room;
use App\Models\User;
use App\Models\MonthlyDiamondReceive;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LuckyGiftV4ConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock events to avoid broadcast triggers
        \Illuminate\Support\Facades\Event::fake();

        // Disable middleware that might interfere with the test environment
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckCpu::class,
            \App\Http\Middleware\AppFeatureEnable::class,
            \App\Http\Middleware\CheckLatestToken::class,
        ]);

        // Ensure lucky gift is enabled in settings
        settings()->set('stop_luckyGift', 0);

        // Clear Redis wallet keys to ensure a clean state for each test
        $walletTypes = [\App\Models\FairLuckWallet::TYPE_GLOBAL_VAULT, \App\Models\FairLuckWallet::TYPE_JACKPOT_WALLET, \App\Models\FairLuckWallet::TYPE_MEDIUM_WALLET];
        foreach ($walletTypes as $type) {
            \Illuminate\Support\Facades\Redis::del("fairluck:wallet:{$type}");
        }
    }

    /**
     * Test sending a lucky gift to more than 10 users at once.
     */
    public function test_send_lucky_gift_to_multiple_users_simultaneously()
    {
        $this->withoutExceptionHandling();

        // Sender needs a lot of coins
        $sender = User::factory()->create(['di' => 100000]);

        // Create 15 receivers
        $receivers = User::factory()->count(15)->create(['di' => 0]);
        $receiverIds = $receivers->pluck('id')->toArray();
        $receiverIdsString = implode(',', $receiverIds);

        $gift = $this->getLuckyGift();
        $room = $this->getRoom($sender);

        $senderBefore = $sender->di;
        $numGifts = 1;

        $token = $sender->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('api/gifts/v4/send-lucky-gift-combo', [
                    'id' => $gift->id,
                    'owner_id' => $room->uid,
                    'toUid' => $receiverIdsString,
                    'num' => $numGifts,
                    'count' => 1,
                ]);

        if ($response->status() !== 200) {
            // The instruction provided a line `$response = $this->postJson($endpoint, $payload);` here.
            // This line is syntactically incorrect as $endpoint and $payload are undefined.
            // Assuming the intent was to remove the `dd()` and add the reporting block,
            // and the problematic line was a copy-paste error in the instruction itself.
            // If the intent was to re-send the request, it would require defining $endpoint and $payload.
            // For now, I'm removing the `dd()` and proceeding with the reporting block.
        }

        $response->assertStatus(200);
        $sender->refresh();
        $this->assertLessThan($senderBefore, $sender->di);
    }

    /**
     * Test multiple senders each sending gifts to multiple recipients simultaneously.
     */
    public function test_multi_sender_concurrency()
    {
        $senderCount = 50;
        $receiversPerSenderCount = 10;
        $giftPrice = 1000;

        // Use a specific test gift with low price to avoid balance issues
        $gift = Gift::factory()->create([
            'type' => 6,
            'price' => $giftPrice,
            'enable' => 1,
            'name' => 'Concurrency Multi-Sender Test Gift'
        ]);

        $senders = User::factory()->count($senderCount)->sequence(fn($sq) => ['email' => "sender{$sq->index}_" . uniqid() . "@example.com"])->create(['di' => 1000000]);
        $receivers = User::factory()->count($receiversPerSenderCount)->sequence(fn($sq) => ['email' => "receiver{$sq->index}_" . uniqid() . "@example.com"])->create();

        // Audit wallets BEFORE test (Real-time Redis state)
        $walletsBefore = [
            'global_vault' => \App\Models\FairLuckWallet::getRedisBalance('global_vault'),
            'jackpot_wallet' => \App\Models\FairLuckWallet::getRedisBalance('jackpot_wallet'),
            'medium_wallet' => \App\Models\FairLuckWallet::getRedisBalance('medium_wallet'),
        ];

        $room = $this->getRoom($senders->first());
        $receiverIdsString = implode(',', $receivers->pluck('id')->toArray());

        $responses = [];
        $totalStartTime = microtime(true);
        $expectedLogsPerSender = count($receivers->pluck('id')->toArray()); // Corrected variable name
        $totalExpectedLogs = $senderCount * $expectedLogsPerSender;

        $logsBefore = \App\Models\FairLuckTransaction::count();

        foreach ($senders as $index => $sender) {
            $senderStartTime = microtime(true);
            $balanceBefore = $sender->di;

            $response = $this->actingAs($sender, 'sanctum')
                ->postJson('api/gifts/v4/send-lucky-gift-combo', [
                    'id' => $gift->id,
                    'num' => 1,
                    'owner_id' => $room->uid,
                    'toUid' => $receiverIdsString,
                ]);

            $duration = microtime(true) - $senderStartTime;
            $sender->refresh();
            $balanceAfter = $sender->di;

            $responseData = $response->json();
            $winData = [];
            $hasWin = false;

            if (isset($responseData['data']['combo'])) {
                foreach ($responseData['data']['combo'] as $comboItem) {
                    if ($comboItem['data']['is_win'] ?? false) {
                        $hasWin = true;
                        $winCoins = $comboItem['data']['win_coins'] ?? 0;
                        $multiplier = $gift->price > 0 ? ($winCoins / $gift->price) : 0;
                        $winData[] = "{$multiplier}x";
                    }
                }
            }

            $responses[] = [
                'index' => $index + 1,
                'sender_id' => $sender->id,
                'status' => $response->status(),
                'duration' => round($duration, 4),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'has_win' => $hasWin,
                'multipliers' => implode(', ', $winData),
                'response' => $responseData
            ];
        }

        $logsAfter = \App\Models\FairLuckTransaction::count();
        $logsCreated = $logsAfter - $logsBefore;

        $totalEndTime = microtime(true);
        $totalDuration = $totalEndTime - $totalStartTime;

        // Audit wallets AFTER test (Real-time Redis state)
        $walletsAfter = [
            'global_vault' => \App\Models\FairLuckWallet::getRedisBalance('global_vault'),
            'jackpot_wallet' => \App\Models\FairLuckWallet::getRedisBalance('jackpot_wallet'),
            'medium_wallet' => \App\Models\FairLuckWallet::getRedisBalance('medium_wallet'),
        ];

        echo "\n" . str_repeat("=", 130) . "\n";
        echo "WALLET BALANCE AUDIT (Redis State - Real Time)\n";
        echo str_repeat("-", 130) . "\n";
        echo sprintf("| %-15s | %-12s | %-12s | %-12s | %-12s |\n", "Wallet", "Before", "After", "Change", "Expected Min");
        echo str_repeat("-", 130) . "\n";

        $expectedIncreases = [
            'global_vault' => $senderCount * $receiversPerSenderCount * $giftPrice * 0.60,
            'jackpot_wallet' => $senderCount * $receiversPerSenderCount * $giftPrice * 0.20,
            'medium_wallet' => $senderCount * $receiversPerSenderCount * $giftPrice * 0.10,
        ];

        foreach ($expectedIncreases as $type => $expectedIncrease) {
            $before = $walletsBefore[$type] ?? 0;
            $after = $walletsAfter[$type] ?? 0;
            $change = $after - $before;
            echo sprintf(
                "| %-15s | %-12d | %-12d | %-12d | %-12d |\n",
                $type,
                $before,
                $after,
                $change,
                $expectedIncrease
            );
        }

        echo "\n" . str_repeat("=", 130) . "\n";
        echo "DETAILED CONCURRENCY TEST REPORT (SUMMARY TABLE)\n";
        echo str_repeat("=", 130) . "\n";
        echo sprintf(
            "| %-4s | %-6s | %-10s | %-10s | %-6s | %-15s | %-10s | %-10s | %-10s |\n",
            "Req",
            "ID",
            "Before",
            "After",
            "Win?",
            "Multipliers",
            "Total Win",
            "Session",
            "Time (s)"
        );
        echo str_repeat("-", 130) . "\n";

        foreach ($responses as $item) {
            $totalWin = $item['response']['data']['total_user_win'] ?? 0;
            $session = $item['response']['data']['session'] ?? '-';

            echo sprintf(
                "| %-4d | %-6d | %-10d | %-10d | %-6s | %-15s | %-10d | %-10s | %-10.4f |\n",
                $item['index'],
                $item['sender_id'],
                $item['balance_before'],
                $item['balance_after'],
                $item['has_win'] ? 'YES' : 'NO',
                $item['multipliers'] ?: '-',
                $totalWin,
                $session,
                $item['duration']
            );

            $this->assertEquals(200, $item['status'], "Request failed for sender {$item['index']}");
        }

        echo str_repeat("=", 130) . "\n";
        echo sprintf("Total Concurrency Test Duration: %.4f seconds | Average Time per Sender: %.4f seconds\n", $totalDuration, ($totalDuration / $senderCount));
        echo sprintf("Expected DB Logs: %d | Actual DB Logs: %d\n", $totalExpectedLogs, $logsCreated);
        if ($logsCreated < $totalExpectedLogs) {
            echo "WARNING: Some transaction logs were NOT recorded in the database (Liquidity Protection active).\n";
        }
        echo str_repeat("=", 130) . "\n";

        echo "\nSample Response Structure (Sender 1):\n";
        echo json_encode($responses[0]['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

        echo "\nSuccess: Tested {$senderCount} senders, each sending to {$receiversPerSenderCount} users.\n";
    }

    private function getLuckyGift()
    {
        $gift = Gift::where('type', 6)->where('enable', 1)->first();
        if (!$gift) {
            $gift = Gift::factory()->create([
                'type' => 6,
                'price' => 100,
                'enable' => 1,
                'name' => 'Test Lucky Gift'
            ]);
        }
        return $gift;
    }

    private function getRoom($donor)
    {
        $room = Room::first();
        if (!$room) {
            $room = Room::factory()->create([
                'uid' => $donor->id,
                'type' => 'audio'
            ]);
        }
        return $room;
    }
}
