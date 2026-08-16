<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gift;
use App\Services\GiftLoadTestService;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;

class GiftLoadTestServiceTest extends TestCase
{
    public function test_gift_load_test_service()
    {
        // 1. تجهيز بيانات وهمية
        $sender = User::factory()->create(['wallet_balance' => 10000]);
        $receiver = User::factory()->create(['wallet_balance' => 5000]);
        $gift = Gift::factory()->create([
            'price' => 100,
            'receiver_value' => 120
        ]);

        $token = 'test-token';
        $url = 'https://example.com';

        // 2. Mock استدعاء my-data
        Http::fake([
            "$url/api/my-data" => Http::response([
                'data' => [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'wallet_balance' => $sender->wallet_balance
                ]
            ], 200),
        ]);

        // 3. Mock Guzzle Client
        $mockClient = Mockery::mock(Client::class);
        $count = 5;
        $num = 2;

        $mockPromises = [];
        for ($i = 1; $i <= $count; $i++) {
            $promise = Mockery::mock(PromiseInterface::class);
            $promise->shouldReceive('wait')->andReturn(new Response(200, [], json_encode(['success' => true])));
            $mockPromises[$i] = $promise;
        }

        $mockClient->shouldReceive('postAsync')
                   ->times($count)
                   ->andReturnUsing(function () use (&$mockPromises, $count) {
                       static $i = 1;
                       return $mockPromises[$i++];
                   });

        // 4. حقن Guzzle Client في الخدمة
        $service = new GiftLoadTestService();
        $data = [
            'token' => $token,
            'url' => $url,
            'count' => $count,
            'concurrency' => 2,
            'id' => $gift->id,
            'owner_id' => $sender->id,
            'toUid' => $receiver->id,
            'num' => $num,
        ];

        // 5. استدعاء الخدمة
        $result = $service->run($data);

        // 6. Assertions
        $this->assertEquals($count, $result['summary']['success']);
        $this->assertEquals(0, $result['summary']['failed']);
        $this->assertEquals($count * $num * $gift->price, $result['expected_sender'] - $result['before_sender']);
        $this->assertEquals($count * $num * $gift->receiver_value, $result['expected_receiver'] - $result['before_receiver']);
    }
}
