<?php

namespace Modules\Chat\Http\Resources;

use App\Helpers\UserCommon;
use Modules\Chat\Entities\ChatMessage;
use Modules\Chat\Entities\ChatRoom;
use Modules\Chat\Entities\MessageAlbum;
use Modules\Chat\Entities\MessageReplay;
use Modules\Chat\Entities\React;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class ChatMessageV2Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    function Replay_page($message_id, $check_room_id)
    {
        $message_id = (int) $message_id;
        $perPage = 15;
        $pageNumber = ceil(ChatMessage::where('chat_room_id', $check_room_id)->where('id', '>', $message_id)->count() / $perPage);
        return $pageNumber;
    }




    function create_at($timeZone = null)
    {
        $createdAt = Carbon::parse($this->created_at);//->setTimezone($timeZone);

        if ($createdAt->isCurrentHour() || $createdAt->isCurrentDay()) {
            if (app()->getLocale() == 'ar') {
                return UserCommon::englishToArabicNumbers($createdAt);
            }
            return $createdAt->isoFormat('h:mm:ss A');
        } else if ($createdAt->isYesterday()) {
            return __('messages.yesterday');
        } else if ($createdAt->isCurrentWeek()) {
            $dayName = $createdAt->locale(app()->getLocale())->dayName; // ترجم اسم اليوم
            return $dayName;
        } else if ($createdAt->isCurrentDay()) {
            $daysSinceCreation = $createdAt->diffInHours(Carbon::now());
            return __('messages.days_ago', ['days' => $daysSinceCreation]);
        } else {
            if (app()->getLocale() == 'ar') {
                return UserCommon::englishToArabicNumbersDate($createdAt);
            }
            return $createdAt->locale(app()->getLocale())->format('Y-m-d');
        }
    }


    public function toArray(Request $request)
    {

        $timeZone = $request->hasHeader('tz') ? $request->header()['tz'][0] : 'UTC';
        $reacts = React::where('chat_message_id', $this->id)->get();
        $albums = MessageAlbum::where('chat_message_id', $this->id)->get();
        $album_array = [];
        foreach ($albums as $album) {
            $album_array[] = [
                'id'      => $album->id,
                'user_id' => $album->user_id,
                'file'    => $album->file,
                'frame'    => $album->frame ? $album->frame : null,
                'type'    => $album->type,
                'duration' => $this->duration ?? '',
            ];
        }
        $replay = MessageReplay::where('message_id', $this->id)->with('from_message', 'from_message.albums')->first();

        if ($replay) {
            $data = $replay->from_message;

            $replay_array = [
                'message_id'      => $data->id,
                'message_user_id' => $data->user_id,
                'message'         => $data->message,
                'message_type'    => $data->type,
                'duration'        => $data->duration,
                'message_albums'  => $data->albums->count() > 0 ?
                    $data->albums->map(function ($item) {
                        return [
                            'id'      => $item->id,
                            'user_id' => $item->user_id,
                            'file'    => $item->file,
                            'type'    => $item->type,
                            'frame'    => $item->frame,
                        ];
                    })
                    : null,
                'page' => $this->Replay_page($data->id, $data->chat_room_id)
            ];
        }
         $authUser =$this->user_id == Auth::user()->id;

        return [
            'replay' => $replay ? $replay_array : null,
            'id' => $this->id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'status' => $this->status,
            'room_owner_id' =>  is_numeric($this->room_owner_id) ? intval($this->room_owner_id) : 0,
            'room_id' =>  is_numeric($this->room_id) ? intval($this->room_id) : 0,
            'type' => $this->type,
            'duration' => $this->duration ?? '',
            // Clean key (was 'chat_room_id ' with a trailing space) so the realtime
            // client can resolve the room; server_room_id mirrors it for the
            // user:#{id} group fan-out path.
            'chat_room_id' => (int) $this->chat_room_id,
            'server_room_id' => (int) $this->chat_room_id,
            'sender_deleted'   => ($this->user_1_deleted && $this->user_2_deleted) ? true : false,
            'receiver_deleted' =>  ($this->user_1_deleted && $this->user_2_deleted) ? true : false,
            'reacts' => $reacts->count() > 0 ? ChatReactResource::collection($reacts) : null,
            'albums' => $albums->count() > 0 ?  $album_array : null,
            'created_at' => $this->create_at($timeZone),
            "date_time" => $this->created_at,
        ];
    }
}
