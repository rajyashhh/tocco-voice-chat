<?php

use App\Models\RoomVisitor;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;
use Modules\Chat\Http\Services\ChatRoomService;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('room-{roomId}', function ($user, $roomId) {
    $data = [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar ?? null,
    ];

    return ['id' => $user->id, 'name' => $user->name]; // Must return user details
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {

    return (int) $user->id === (int) $id;
});
// Broadcast::channel('room-{roomId}', function ($user, $roomId) {
//     return  $roomId;
// });

Broadcast::channel('room-{roomId}-{userId}', function ($user, $roomId, $userId) {
    return  $userId == $user->id;
});


Broadcast::channel('enter-user-room', function ($user) {
    return $user;
});

Broadcast::channel('test-channel', function () {
    return true; // No authentication required
});


Broadcast::channel('presence.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id
        ? ['id' => $user->id, 'name' => $user->name, 'device_token' => $user->device_token]
        : false;
});

Broadcast::channel('room.boom.rewards.{roomId}', function ($user, $roomId) {
    return [
        'id'   => $user->id,
       // 'name' => $user->name,
    ];
});

Broadcast::channel('chat.room.{chatRoomId}', function ($user, $chatRoomId) {
    try {
        $chatRoomService = app(ChatRoomService::class);
        $checkRoom = $chatRoomService->getCreateChatRoomId($chatRoomId);
        if (!$checkRoom){
            return false;
        }
        $user->update(['current_room_chat' => $checkRoom->id]);
        $chatRoomService->markMessagesAsSeen($checkRoom, $user);
        $user2 = $chatRoomService->getUserInChatRoom($checkRoom, $user);
        $chatRoomService->handleChatOpenEvent($checkRoom, $user, $user2);

        return [
            'id'   => $user->id,
            'name' => $user->name,
        ];
    } catch (\Exception $e) {
        \Log::error('Broadcasting auth failed for chat.room', [
            'chat_room_id' => $chatRoomId,
            'user_id' => $user->id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
});

Broadcast::channel('pk.battle.{creatorId}', function ($user, $creatorId) {
    return [
        'id'   => $user->id,
        // 'name' => $user->name,
        'creator_id' => $creatorId,
    ];
});
