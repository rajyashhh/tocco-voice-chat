<?php
namespace Modules\Chat\Http\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\MessageReplay;

class MessageRepository
{
    // Update message status
    public function updateMessageStatus(ChatMessage $message, $status)
    {
        $message->status = $status;
        return $message->update();
    }

    // Get user's notification ID
    public function getUserNotificationId($userId)
    {
        return DB::table('users')->where('id', $userId)->value('notification_id');
    }

    // Create a message replay
    public function createMessageReplay($messageId, $fromMessageId)
    {
        return MessageReplay::create([
            'message_id' => $messageId,
            'from_message_id' => $fromMessageId,
        ]);
    }

    // Find message by ID
    public function findMessageById($id)
    {
        return ChatMessage::find($id);
    }
}
