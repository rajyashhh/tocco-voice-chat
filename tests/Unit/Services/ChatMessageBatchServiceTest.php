<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ChatMessageBatchService;
use Modules\Chat\Entities\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ChatMessageBatchServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatMessageBatchService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChatMessageBatchService::class);
    }

    public function test_mark_messages_as_received_in_batch()
    {
        $roomIds = [];

        for ($i = 1; $i <= 10; $i++) {
            $roomIds[] = $i;

            for ($j = 1; $j <= 1000; $j++) {
                ChatMessage::create([
                    'chat_room_id' => $i,
                    'user_id' => $j % 5 + 1,
                    'message' => "Test message $j",
                    'status' => 'sended',
                ]);
            }
        }

        $result = $this->service->markMessagesAsReceivedInBatch($roomIds, 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['total_rooms']);
        $this->assertEquals(2, $result['batches_processed']);
        $this->assertGreaterThan(0, $result['total_messages_updated']);

        $updatedCount = ChatMessage::where('status', 'received')->count();
        $this->assertGreaterThan(0, $updatedCount);
    }

    public function test_mark_messages_as_received_with_empty_room_ids()
    {
        $result = $this->service->markMessagesAsReceivedInBatch([], 1);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['total_rooms']);
        $this->assertEquals(0, $result['total_messages_updated']);
        $this->assertEquals(0, $result['batches_processed']);
    }

    public function test_update_message_status()
    {
        $message = ChatMessage::create([
            'chat_room_id' => 1,
            'user_id' => 1,
            'message' => 'Test message',
            'status' => 'sended',
        ]);

        $result = $this->service->updateMessageStatus($message->id, 'received');

        $this->assertTrue($result);
        $this->assertEquals('received', $message->fresh()->status);
    }

    public function test_update_room_messages_status()
    {
        for ($i = 1; $i <= 100; $i++) {
            ChatMessage::create([
                'chat_room_id' => 1,
                'user_id' => $i % 3 + 1,
                'message' => "Test message $i",
                'status' => 'sended',
            ]);
        }

        $updated = $this->service->updateRoomMessagesStatus(1, 1);

        $this->assertGreaterThan(0, $updated);

        $user1Messages = ChatMessage::where('chat_room_id', 1)
            ->where('user_id', 1)
            ->where('status', 'sended')
            ->count();

        $this->assertGreaterThan(0, $user1Messages);

        $otherMessages = ChatMessage::where('chat_room_id', 1)
            ->where('user_id', '!=', 1)
            ->where('status', 'received')
            ->count();

        $this->assertGreaterThan(0, $otherMessages);
    }

    public function test_delete_old_messages_in_batch()
    {
        $roomIds = [1, 2, 3, 4, 5];

        for ($i = 1; $i <= 5; $i++) {
            for ($j = 1; $j <= 100; $j++) {
                ChatMessage::create([
                    'chat_room_id' => $i,
                    'user_id' => 1,
                    'message' => "Old message $j",
                    'created_at' => now()->subDays(40),
                ]);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            for ($j = 1; $j <= 50; $j++) {
                ChatMessage::create([
                    'chat_room_id' => $i,
                    'user_id' => 1,
                    'message' => "New message $j",
                    'created_at' => now()->subDays(5),
                ]);
            }
        }

        $result = $this->service->deleteOldMessagesInBatch($roomIds, 30);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['total_deleted']);
        $this->assertGreaterThan(0, $result['batches_processed']);

        $oldMessages = ChatMessage::where('created_at', '<', now()->subDays(30))->count();
        $this->assertEquals(0, $oldMessages);

        $newMessages = ChatMessage::where('created_at', '>', now()->subDays(30))->count();
        $this->assertGreaterThan(0, $newMessages);
    }

    public function test_batch_performance()
    {
        $roomIds = [];

        for ($i = 1; $i <= 50; $i++) {
            $roomIds[] = $i;

            for ($j = 1; $j <= 1000; $j++) {
                ChatMessage::create([
                    'chat_room_id' => $i,
                    'user_id' => $j % 10 + 1,
                    'message' => "Test message $j",
                    'status' => 'sended',
                ]);
            }
        }

        $startTime = microtime(true);
        $result = $this->service->markMessagesAsReceivedInBatch($roomIds, 1);
        $duration = (microtime(true) - $startTime) * 1000;

        $this->assertTrue($result['success']);
        $this->assertEquals(50, $result['total_rooms']);
        $this->assertEquals(10, $result['batches_processed']);
        $this->assertLessThan(30000, $duration);

        $updatedCount = ChatMessage::where('status', 'received')->count();
        $this->assertGreaterThan(0, $updatedCount);
    }

    public function test_error_handling()
    {
        $result = $this->service->updateMessageStatus(99999, 'received');
        $this->assertFalse($result);
    }

    public function test_logging()
    {
        $roomIds = [1, 2, 3];

        for ($i = 1; $i <= 3; $i++) {
            for ($j = 1; $j <= 100; $j++) {
                ChatMessage::create([
                    'chat_room_id' => $i,
                    'user_id' => 1,
                    'message' => "Test message $j",
                    'status' => 'sended',
                ]);
            }
        }

        $result = $this->service->markMessagesAsReceivedInBatch($roomIds, 1);

        $this->assertTrue($result['success']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('duration_ms', $result);
        $this->assertArrayHasKey('batches_processed', $result);
    }
}
