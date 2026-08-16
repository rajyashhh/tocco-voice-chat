<?php

namespace Modules\Chat\Http\Resources;

use App\Helpers\Common;
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

class ChatMessageResource extends JsonResource
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
    // function create_at($timeZone = null) {
    //     // $createdAt = Carbon::parse($this->created_at);
    //     $createdAt = $this->created_at->timezone($timeZone)->toDateTimeString();
    //     if ($createdAt->isCurrentHour()) {
    //         return  Carbon::parse($this->created_at)->format('h:m:i A');
    //     }

    //    else if ($createdAt->isCurrentDay()) {
    //         // Calculate the number of days since the creation date
    //         $daysSinceCreation = $createdAt->diffInHours(Carbon::now());
    //         return  Carbon::parse($this->created_at)->format('h:m:i A');
    //     }
    //     elseif ($createdAt->isYesterday()) {
    //         return 'Yesterday';
    //     }
    //     else if ($createdAt->isCurrentWeek()) {
    //         // Calculate the number of days since the creation date
    //         $daysSinceCreation = Carbon::parse($this->created_at)->dayName;
    //         return $daysSinceCreation ;
    //     }
    //     else{
    //         return  Carbon::parse($this->created_at)->format('Y-m-d');
    //     }
    // }



    function create_at($timeZone = null)
    {
        $createdAt = Carbon::parse($this->created_at);

        if ($timeZone) {
            $createdAt->setTimezone($timeZone);
        }

        // Get current time in the same timezone for accurate comparison
        $now = Carbon::now($timeZone ?: 'UTC');

        if ($createdAt->isSameHour($now) || $createdAt->isSameDay($now)) {
            if (app()->getLocale() == 'ar') {
                return UserCommon::englishToArabicNumbers($createdAt);
            }
            return $createdAt->isoFormat('h:mm:ss A');
        } else if ($createdAt->isYesterday()) {
            return __('messages.yesterday');
        } else if ($createdAt->isSameWeek($now)) {
            $dayName = $createdAt->locale(app()->getLocale())->dayName; // ترجم اسم اليوم
            return $dayName;
        } else {
            if (app()->getLocale() == 'ar') {
                return UserCommon::englishToArabicNumbersDate($createdAt);
            }
            return $createdAt->locale(app()->getLocale())->format('Y-m-d');
        }
    }


    public function toArray(Request $request)
    {
        // Get timezone from request header with validation
        $timeZone = getTimezone(); // Use system timezone as default instead of UTC
        if ($request->hasHeader('tz')) {
            $requestedTimezone = $request->header()['tz'][0];
            // Validate timezone
            if (in_array($requestedTimezone, timezone_identifiers_list())) {
                $timeZone = $requestedTimezone;
            }
        }
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

        return [
            'replay' => $replay ? $replay_array : null,
            'id' => $this->id,
            // Offline-sync dedup/ordering keys, aligned with SyncMessageResource.
            // Carried in the broadcast payload (this resource feeds the
            // BroadcastChatMessage job) so a dual-transport client can drop the
            // duplicate copy by client_uuid and order by server_seq across the
            // Pusher and Centrifugo streams. Pusher-only mode simply ignores them.
            'server_seq' => $this->server_seq !== null ? (int) $this->server_seq : null,
            'client_uuid' => $this->client_uuid,
            'user_id' => $this->user_id,
            // Sender identity carried INSIDE the broadcast payload so the
            // foreground in-app heads-up banner (and a brand-new conversation's
            // list row, before the rooms-list sync lands) can render the sender's
            // name + avatar instead of the generic «Message» fallback. The system
            // FCM push already had this from the Eloquent model; the realtime path
            // did not. The relation is eager-loaded only on the single found
            // message at the send site (MessageService::findMessageById), so this
            // adds no N+1 to history/list endpoints that reuse this resource.
            'user' => [
                'user_id'        => $this->user_id,
                'name'           => $this->user?->name,
                'user_image'     => $this->user?->profile?->avatar,
                'has_color_name' => $this->user ? Common::hasInPack($this->user, 18, true) : false,
            ],
            'message' => $this->message,
            'status' => $this->status,
            'room_owner_id' =>  is_numeric($this->room_owner_id) ? intval($this->room_owner_id) : 0,
            'room_id' =>  is_numeric($this->room_id) ? intval($this->room_id) : 0,
            'type' => $this->type,
            'duration' => $this->duration ?? '',
            // The realtime client resolves the room from this key
            // (RealtimeMessageMapper.serverRoomId reads chat_room_id). The old key
            // carried a trailing space, so group fan-out over user:#{id} could not
            // resolve the room and the message never reached drift. Both the clean
            // key and the server_room_id alias are emitted so _onUserPublication
            // resolves the room either way.
            'chat_room_id' => (int) $this->chat_room_id,
            'server_room_id' => (int) $this->chat_room_id,
            // Lets the client classify a dynamically-created room. The authoritative
            // signal is the room type (chat_rooms.type === 'group'); read from the
            // loaded room relation to stay N+1-free in 1:1 message lists (which never
            // load `room`, so they correctly resolve to false). The group send/sync
            // paths load `room`, so group messages resolve to true.
            'is_group' => $this->relationLoaded('room')
                ? (($this->room->type ?? null) === 'group')
                : false,
            'sender_deleted' => $this->user_1_deleted ? true : false,
            'receiver_deleted' => $this->user_2_deleted ? true : false,
            'reacts' => $reacts->count() > 0 ? ChatReactResource::collection($reacts) : null,
            'albums' => $albums->count() > 0 ?  $album_array : null,
            'created_at' => $this->create_at($timeZone),
            "date_time" => $this->created_at,
            'local_id'             => $request->local_id,
        ];
    }
}
