<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\Request;
use App\Http\Resources\CountryResource;
use Illuminate\Http\Resources\Json\JsonResource;

class NowRoomUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    // public function toArray($request)
    // {
    //     $pass_status = false;
    //     $now_room    = @$this->room;
    //     if ($now_room) {
    //         if ($now_room->room_pass) {
    //             $pass_status = true;
    //         }
    //     }

    //     if (!@$now_room->is_live &&  @$now_room->type  == 'live') {
    //         return [];
    //     }


    //     return [
    //         'is_in_room'      => @$this->now_room_uid != 0,
    //         'uid'             => @(int)$this->now_room_uid,
    //         'is_mine'         => @$this->id == @$this->now_room_uid,
    //         'password_status' => $pass_status,
    //         "id"              => @$now_room->id,
    //         "room_name"       => @$now_room->room_name,
    //         "room_cover"      => @$now_room->room_cover,
    //         "room_background" => @$now_room->final_room_image,
    //         "mode"            => @$now_room->mode,
    //         'giftPrice'       => @$now_room->session_string,
    //         'room_type'       => @$now_room->type ?? '',
    //         'is_live'       => (boolean)@$now_room->is_live ?? 0,
    //     ];

    // }


     public function toArray($request)
    {
        // Common::setHourHot($this->uid);
        $pass_status = false;
    //     $now_room    = @$this->room;
    //     if ($now_room) {
    //         if ($now_room->room_pass) {
    //             $pass_status = true;
    //         }
    //     }

    //     if (!@$now_room->is_live &&  @$now_room->type  == 'live') {
    //         return [];
    //     }
        $userId = \Auth::id();
        $pk = $this->lastPk;
        $achievement_images = [];
        if (@$this->owner?->medals) {
            foreach (@$this->owner?->medals as $medal) {
                if ($medal->achievementLevel && $medal->achievementLevel->achievement && $medal->achievementLevel->achievement->type?->value == 'room_target') {
                    $achievement_images[] = $medal->achievementLevel->valid_image;
                }
            }
        }
        $isParty = $this->roomCategory && $this->roomCategory->type === 'party';
        $have_luck_box = $this->boxUse->where("is_closed", 0);
        $isHideCountry = $this?->owner?->getPackWithTypeV2(13);

        if ($this->owner->relationLoaded('chatRoomsAsUser') || $this->owner->relationLoaded('chatRoomsAsUser2')) {
            $chatRoom = $this->owner->chatRoomsAsUser->first() ?? $this->owner->chatRoomsAsUser2->first();
        }

        $agency_joined = $this->owner->agency;
        if ($agency_joined) {

            $agency_joined = [
                'id' => $agency_joined->id,
                'name' => $agency_joined->name,
            ];
        } else {
            $agency_joined = (object)[];
        }

        /**@var Room $this*/
        $data = [
            'id' => $this->id,
            'owner_id' => $this->uid ?: 0,
            //            'owner_uuid' => $this->owner?->uuid ?: 0,
            'owner_uuid' => $this->owner?->uuid_v2 ?: 0,
            'owner_name' => $this->owner?->name ?: '',
            'room_name' => $this->owner?->name ?: '',
            'owner_image' => $this->owner?->profile?->avatar ?: '',
            'room_id' => (string)($this->id ?: 0),
            'owner_special_id'          => $this->owner?->specialId?->ware?->show_img ?? "",
            'owner_image_color'          => $this->owner?->color_image,
            'owner_task_room_id' => $this->whenLoaded('taskStream', fn() => $this->taskStream?->id),
            'current_task_room_id' => $this->whenLoaded('taskStreamRoom', fn() => $this->taskStreamRoom?->task_stream_id),
            'chat_id' => $chatRoom->id ?? null,
            'owner_deleted_at' => $this->owner?->deleted_at,
            'unread_messages_count' => $chatRoom->unread_messages_count ?? 0,
            'name' => $this->room_name ?: '',
            "mode" => $this->mode,
            'agency' => $agency_joined,
            'visitors_count' => $this->count_room_socket_v2,
            'cover' => $this->room_cover ?: '',
            'room_level_image'   => $this->type == 'audio' ? @$this->roomLevel->img ?? '' : '',
            //            'class' => $this->myClass ?: new \stdClass(),
            //            'type' => $this->myType ?: new \stdClass(),
            'is_hot' => $this->hot ?: 0,
            'session' => $this->session_string,
            'giftPrice' => $this->session_string,
            'is_popular' => $this->is_popular ?: 0,
            'room_status' => $this->room_status,
            'password_status' => (bool) $this->room_pass,
            'room_intro' => $this->room_intro ?? '',
            'max_admin' => $this->max_admin ?: '',
            'is_recommended' => $this->is_recommended ?: 0,
            'lang' => $this->lang ?: '',
            'is_pk' => (bool) $pk,
            'is_party' => $isParty,
            'room_background' => $this->final_room_image,
            'stream_type' => $this->type ?? 'audio',
            'is_live' => (bool)$this->is_live,
            'is_lucky_box' => $this->is_lucky_box ?? false,
            'country' => $this->owner?->country
                ? new CountryResource($this->owner->country)
                : [
                    'id' => 0,
                    'name' => '',
                    'flag' => '',
                    'lang' => '',
                    'phone_code' => ''
                ],
                
            'have_luck_box' => (bool) $have_luck_box,
            'achievement_images' => $achievement_images,
            /** refactored */
            'medals'               =>  @$this->owner?->enabledMedals ?? [],
            //            'medals'               => @$this->owner?->medals()?->where('is_enable', true)->get() ?? [],
            $this->mergeWhen($this->distance, [
                'distance' => $this->distance,
            ]),
            $this->mergeWhen($this->relationLoaded('game'), [
                'game' => $this->mode == 4 && $this->game ? new \App\Http\Resources\AllGameResource($this->game) : new \stdClass(),
            ]),
            $this->mergeWhen($this->relationLoaded('roomVisitorUsers'), [
                'visitors_images' => $this->getVisitorsImages(),
            ]),
            'country_hidden' => $isHideCountry,
        ];

        if ($request['show']) {

            $micString = $this->microphones()
                ->orderBy('position')
                ->get()
                ->map(function ($mic) {
                    $userId = $mic->user_id ?? 0;
                    $status = $mic->status ?? 0;

                    if ($userId > 0) {
                        return "{$userId}#{$status}";
                    } else {
                        return (string)$status;
                    }
                })
                ->implode(',');

            $data = array_merge($data, [
                'room_users' => Common::get_room_users_2($this->owner()?->id, $request->user()->id),
                'background' => $this->final_room_image ?: $this->room_background,
                //                'mics' => $this->microphone ? explode(',', $this->microphone) : [],
                'mics' => $micString ? explode(',', $micString) : [],
                'is_mics_free' => $this->free_mic ?: 0,
                'owner' => $this->owner(),
                'admins' => $this->admins(),
                'admins_ids' => $this->admins()->pluck('id'),
                'speak_ban_list' => $this->banList(),
                'muted_list' => $this->muteList(),
                'black_list' => $this->blackList(),
                'created_at' => $this->created_at,
            ]);
        }
        return $data;
    }


    protected function owner()
    {
        return new UserResource(User::query()->find($this->uid));
    }

    protected function admins()
    {
        $ids = explode(',', $this->room_admin);
        $ids = $this->removeOwner($ids);
        return UserResource::collection(User::query()->whereIn('id', $ids)->get());
    }


    protected function visitors()
    {
        // Use roomVisitorUsers relation (HasManyThrough) to avoid N+1 query
        // Filter out owner if needed
        $visitors = $this->roomVisitorUsers()->where('users.id', '!=', $this->uid)->get();
        return UserResource::collection($visitors);
    }

    protected function blackList()
    {
        $ids = explode(',', $this->room_black);
        return UserResource::collection(User::query()->whereIn('id', $ids)->get());
    }

    protected function banList()
    {
        $ids = explode(',', $this->room_speak);
        return UserResource::collection(User::query()->whereIn('id', $ids)->get());
    }

    protected function muteList()
    {
        $ids = explode(',', $this->room_sound);
        return UserResource::collection(User::query()->whereIn('id', $ids)->get());
    }

    protected function removeOwner($ids)
    {
        if (($key = array_search($this->uid, $ids)) !== false) {
            unset($ids[$key]);
        }
        return $ids;
    }
}
