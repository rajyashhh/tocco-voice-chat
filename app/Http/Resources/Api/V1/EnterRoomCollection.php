<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Helpers\Common;

use App\Models\configesModel;
use App\Models\Pk;
use App\Models\RequestBackgroundImage;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\CP\Entities\CpRoomHistory;

class EnterRoomCollection extends JsonResource
{


    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $pks     = $this->getRoomTwoLastPk($this->id);

        request()->type = 1;
        $owner = $this->owner;
        // owner can be null if the owner account was deleted
        $vip_level_img = $owner ? Common::ovip_center_rank_img_v2($owner) : '';

        // CP pairs map to numbered mic seats (a room has only a handful). The
        // unbounded get() could scan stale rows if exit-cleanup was ever missed;
        // a generous LIMIT 100 (>> any real seat count) bounds the worst case
        // while leaving the rendered cp_indexs identical for real rooms. No
        // orderBy so the default row order the client receives is unchanged.
        $cpRoomHistories = CpRoomHistory::where('room_id', $this->id)->limit(100)->get(['index1', 'index2']);

        $indices = $cpRoomHistories->map(function ($history) {
            return [$history->index1, $history->index2];
        })->toArray();

        /** @var User $owner*/
        return [
            "id"                  => $this->id, // room id
            "mode"                => $this->mode, // room mode
            "owner_id"            => $this->uid, // owner id
            "uuid"                => $owner?->uuid ?? '', // owner uuid
            "room_name"           => $this->room_name, // room name
            "owner_name"          => @$owner->name ?? '', // owner name
            "room_cover"          => $this->room_cover, // room cover
            "room_background"     => $this->final_room_image, // room background
            "owner_image"         => $owner?->profile?->avatar ?: '', // owner image (eager-loaded profile)
            'owner_task_room_id'  => $this->taskStream?->id,
            'current_task_room_id' => $this->taskStreamRoom?->task_stream_id,
            "giftPrice"           => $this->session_string ?: '', // gift price
            "password_status"     => !($this->room_pass == ""), // room password state
            "admins"              => explode(',', $this->room_admin ?? ''), // room admins
            // user_id => [permission keys] | null (null = ALL powers).
            "admin_permissions"   => Cache::remember(
                'room:' . $this->id . ':admin_perms',
                300,
                fn () => app(\App\Repositories\RoomAdministratorRepository::class)
                    ->getAdminPermissions($this->id)
            ),
            "pk"                  => (@$pks[0]) && $pks[0]->end_at >= now() ? new PkCollection($pks[0]) : new \stdClass(), // all pk data
            "room_intro"          => $this->room_intro, // room intro
            //            "microphones"         => $this->getMicrophones($this->microphone, $this->main_microphone), // seat states
            "microphones"         => $this->getMicrophones2(), // seat states
            "cp_indexs"           => $indices, // cp
            "charisma_status"     => ($this->charizma_status) ? true : false, // isCharisma
            // Server-authoritative cumulative room charisma for the room OWNER badge
            // (the owner holds the room badge without sitting on a numbered mic).
            // Floored coins from the durable per-room store; 0 when charisma is off
            // or the owner has none. Lets a late-joiner render the owner badge with
            // zero client resync.
            "owner_charisma_total" => $this->charizma_status
                ? \App\Services\RoomCharismaStore::total((int) $this->id, (int) $this->uid)
                : 0,
            "is_comment_closed"   => $this->is_comment_closed,
            'is_live' => (bool) ($this->is_live ?? false),
            // Cumulative UNIQUE viewers of the CURRENT broadcast (deduped in
            // live_session_viewers, reset on end-live). 0 for audio rooms.
            'live_viewers_total'  => (int) ($this->live_viewers_total ?? 0),
            // Does the requesting user follow the host? Drives the live
            // "متابعة" pill (hidden for the host / existing followers).
            'is_following_owner'  => (bool) Common::IsFollow(auth()->id(), $this->uid),
            "room_rule"           => Common::getConfig('room_rule' . (app()->getLocale() != 'ar' ? '_en' : '')), // room rules
            //////////////////////////////////////////////////////////
            ///
            ///
            // 60s TTL: getSettingsValue() force-refreshes (DB hit) on every entry;
            // panel changes still propagate within a minute.
            'lucky_gift_coins'    => Cache::remember('enter_lucky_gift_coins', 60, fn () => Common::getSettingsValue('lucky_gift_coins')) ?: 2000,
            "room_id_num"         => $this->numid,
            "room_status"         => (string)$this->room_status,

            "name"                => @$this->name ?? '',
            "room_pass"           => $this->room_pass,
            'room_type'           =>  app()->getLocale() === 'ar' ? $this->roomCategory?->name  ?? $this->roomCategory?->name_en : $this->roomCategory?->name_en ?? $this->roomCategory?->name,
            'room_level_image'   => $this->type == 'audio' ? @$this->roomLevel->img ?? '' : '',
            "hot"                 => '',
            "microphone"          => $this->microphone,
            "room_welcome"        => $this->room_welcome,
            "session"             => $this->session,
            "room_family"         => is_null($this->family) ? new \stdClass() : [
                'family_id'    => @$this->family->id ?? '',
                'family_name'  => @$this->family->name ?? '',
                'family_level' => @$this->family->level ?? [],
            ],

            'avatar' => [
                'original' => $this->room_cover ?? '',
                'thumbnail' =>  $this->avatar_thumb ?? Common::getImageUrl($this->room_cover, 'thumbnail'),
                'medium' => $this->avatar_medium ?? Common::getImageUrl($this->room_cover, 'medium'),
                'large' => $this->avatar_large ?? Common::getImageUrl($this->room_cover, 'large'),
            ],
            "is_pk"               => (@$pks[0]) && $pks[0]->end_at >= now() ? $pks[0]->status : 0,
            "show_pk"             => @$this->is_show_pk ?? 0,
            'top_user'            => new \stdClass(),
            'owner_sound'         => 1,
            'ban_users'           => [],
            'owner_special_id'          => @$owner?->specialId?->ware?->show_img ?? "",
            'owner_image_color'          => @$owner?->color_image,
            'owner_avatar'        => @$owner->profile->avatar ?? '',
            'owner_vip_level'     => (int) ($owner?->UserVip?->level ?? 0),
            'owner_vip_img'     => $vip_level_img,
            'vip' => [
                'id'        => 1,
                'level'     =>  0,
                'name'      =>  '',
                'price'     => 0,
                'img_old'     =>  '',
                'img'     =>  '',
                'image'     => '',
                'image_from_wares'     => '',
                'expire'    =>  0,
                'ware_id' =>  0,
                'color' =>  '',
                'vip_gifts' => 0,
                'vip_upload_gif' => 0,
                'colored_name' =>  '',
            ],
            'owner_country'        =>        $owner && $owner->country
                ? [
                    'id' => $owner->country->id,
                    'name' => $this->country ? (app()->getLocale() == 'en' ? $owner->country->e_name : $owner->country->name) : '',
                    'flag' => $owner->country->flag,
                    'lang' => $owner->country->lang,
                    'phone_code' => $owner->country->phone_code
                ]
                : [
                    'id' => 0,
                    'name' => '',
                    'flag' => '',
                    'lang' => '',
                    'phone_code' => ''
                ],
            'room_visitors_count' => $this->count_room_socket_v2 ?? 1,
            'boxes'               => [],
            'muted_users'         => $this->muted_users,
            'youtube_key'         => Cache::remember('enter_youtube_key', 600, fn () => configesModel::query()->where("name", "youtube_key")->first()?->value ?? ""),
            'stream_type'         =>  $this->type ?? 'audio',
            'room_keys' => [
                "comment_room_key" => (string)(Common::getConfig('comment_room_key') ?? 13456489535)
            ],
            'writing_disabled'    => ($this->writing_disabled) ? true : false,
            'show_welcom_animation' => settings()->get('show_welcom_enmation') == 'on' ? true : false,
            'private_comment_price' => (Common::getConfig('private_comment_price') ?? 100),
            'game'               =>  $this->mode == 4 && $this->game ? new \App\Http\Resources\AllGameResource($this->game) : new \stdClass(),
            "game_key" => (string)Common::getConfig('comment_room_key') ??  (string)13456489535,
            'room_level'   => [
                'name' => app()->getLocale() === 'ar' ? @$this->level->name_ar ?? '' : @$this->level->name_en ?? '',
                'image'  => @$this->level->image ?? '',
                'exp' => @$this->exp ?? 0,
                'level_num' => @$this->level->level ?? 0,
            ],
        ];
    }

    private function getRoomTwoLastPk(int $roomId)
    {
        return Pk::query()
            ->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();
    }


    /*   private function getBoxes()
    {
        return BoxUse::query()
                     ->with('user', fn($q) => $q->withoutAppends()->select(['id', 'name']))
                     ->where('room_uid', $this->uid)
                     ->where('not_used_num', '>', 0)
                     ->where('unused_coins', '>', 0)
                     ->whereDoesntHave('picks', function ($q) {
                         $q->where('user_id', $this->userId);
                     })
                     ->get();
    }*/

    private function getTopUser(int $roomOwner)
    {

        return @$this->topUser;
    }

    /**
     * @return mixed
     */
    public function getRoomBackground()
    {
        return $this->mode == '3' ? 'custom_image/back-black.png' : ((@RequestBackgroundImage::where('status', 1)->where('owner_room_id', $this->uid)->orderByDesc('id')->first())->img ??
            $this->room_background ??
            @DB::table('backgrounds')->where('enable', 1)->orderBy('id', 'asc')->limit(1)->first()->img);
    }

    public function getOwnerSound($ownerId, $roomSound)
    {
        $roomSound = explode(',', $roomSound);
        return in_array($ownerId, $roomSound);
    }

    private function getBans($roomSpeak)
    {
        $roomSpeak = trim($roomSpeak);
        if ($roomSpeak == '') return [];
        $bans      = [];
        $uid_black = explode(',', trim($roomSpeak));
        foreach ($uid_black as $b) {
            $u      = explode('#', trim($b));
            $bans[] = $u[0];
        }
        return $bans;
    }

    private function getRoomVisitorCount($roomVisitors)
    {
        $roomVisitors = trim($roomVisitors);
        if ($roomVisitors == '') return 1;
        return count(explode(',', $roomVisitors)) + 1;
    }

    private function getMicrophones($microphones, $mainMicrophone)
    {
        $microphones = trim($microphones);
        $mainMicrophone = trim($mainMicrophone);
        if ($microphones == '') return [];
        $microphones = explode(',', $microphones);
        $mainMicrophone = explode(',', $mainMicrophone);
        $arr         = ['0', '-1', '-2'];

        // this users id with 1020#-1
        $usersIds = array_diff($microphones, $arr);

        if (count($usersIds) > 0) {
            //get all users with ids
            $users = User::withoutAppends()->with('profile')->whereIn('id', $usersIds)->select(['id', 'name'])->get();
        }

        for ($i = 0, $j = 0; $i < count($microphones); $i++) {
            $mic = $microphones[$i];
            if ($mic == '0') {
                $microphones[$i] = 'empty';
            } elseif ($mic == '-1') {
                $microphones[$i] = 'locked';
            } elseif ($mic == '-2') {
                $microphones[$i] = 'muted';
            } else {
                //                $microphones[$i] = $users[$j]->setAppends([])->toArray();
                $user = $users->where('id', $microphones[$i])->first();
                if ($user) {
                    $microphones[$i] = [
                        'id'   => $user->id,
                        'name' => $user->name,
                        'img'  => $user->profile?->avatar ?? '',
                        'seat_condition' => ($mainMicrophone[$i] == '0') ? 'empty' : ((($mainMicrophone[$i] == '-1') ? 'locked' : (($mainMicrophone[$i] == '-2') ? 'muted' : 'empty'))),
                    ];
                    $j++;
                } else {
                    $microphones[$i] = 'empty';
                }
            }
        }
        return $microphones;
    }

    private function getMicrophones2()
    {
        $microphones = $this->microphones
            ->sortBy('position')
            ->values();

        $maxPosition = $microphones->max('position') ?? 0;

        $micsByPosition = $microphones->keyBy('position');

        // Server-authoritative charisma totals for every seated user, read ONCE
        // (single HMGET) so a late-joiner renders correct seat badges on entry
        // with zero client resync. Skipped entirely when charisma is off.
        $charismaTotals = [];
        if ($this->charizma_status) {
            $seatedIds = $microphones->map(fn ($m) => $m->user?->id)->filter()->values()->all();
            $charismaTotals = \App\Services\RoomCharismaStore::totals((int) $this->id, $seatedIds);
        }

        $result = [];
        for ($i = 0; $i <= $maxPosition; $i++) {
            if (!isset($micsByPosition[$i])) {
                $result[] = 'empty';
                continue;
            }

            $mic = $micsByPosition[$i];
            $status = (string) $mic->status;

            $seatCondition = match ($status) {
                '0'  => 'empty',
                '-1' => 'locked',
                '-2' => 'muted',
                default => 'empty'
            };

            if (!$mic->user) {
                $result[] = $seatCondition;
            } else {
                $result[] = [
                    'id'   => $mic->user->id,
                    'name' => $mic->user->name,
                    'img'  => $mic->user->profile?->avatar ?? '',
                    'seat_condition' => $seatCondition,
                    // Floored cumulative room charisma for this seat (0 if none).
                    'charisma_total' => $charismaTotals[(int) $mic->user->id] ?? 0,
                ];
            }
        }

        return $result;
    }


    private function getUserType($roomAdmin, $roomJudge)
    {
        $userType = 5;
        [$isAdminInRoom, $roomAdmin] = $this->getAdminAndType(trim($roomAdmin));
        //        [$isUserIsJudge, $roomJudge] = $this->getAdminAndType(trim($roomJudge));

        if ($isAdminInRoom) $userType = 2;
        //        if($isUserIsJudge) $userType = 4;

        return [$userType, $roomAdmin];
    }

    private function getAdminAndType($roomAdmins)
    {
        $isAdminInRoom = false;
        $roomAdmin     = explode(',', $roomAdmins ?? '');

        if (in_array((string)auth()->id(), $roomAdmin)) $isAdminInRoom = true;
        return [$isAdminInRoom, $roomAdmin];
    }

    /*private function getJudgeAndType($roomJudge)
    {
        $isUserIsJudge = false;
        $roomJudge     = explode(',', $roomJudge ?? '');

        if (in_array((string)$this->userId, $roomJudge)) $isUserIsJudge = true;
        return [$isUserIsJudge, $roomJudge];
    }*/
}
