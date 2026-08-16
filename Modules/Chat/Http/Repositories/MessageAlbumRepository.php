<?php
namespace Modules\Chat\Http\Repositories;

use Modules\Chat\Entities\MessageAlbum;

class MessageAlbumRepository
{
    public function createAlbum($chatRoom, $message, $user, $file, $fileName, $type, $frame = null)
    {
        $album = MessageAlbum::create([
            'chat_room_id' =>$chatRoom->id,
            'chat_message_id' => $message->id,
            'user_id' => $user->id,
            'file' => $fileName,
            'type' => $type,
            'frame' => $frame
        ]);
        return $album;
    }
}
