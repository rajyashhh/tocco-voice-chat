<?php

namespace App\Http\Resources\Api\V1;
use App\Helpers\StorageHelper;

use App\Models\Pk;
use Carbon\Carbon;
use App\Models\Pack;
use App\Models\Room;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Models\FamilyUser;
use App\Models\FamilyLevel;
use App\Models\UserSetting;
use App\Facades\UserHandling;
use Modules\Vip\Entities\Vip;
use App\Helpers\UserPackHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class MyDataResource extends JsonResource
{
    public function toArray($request)
    {

        $family = $this->family;
        $f = null;

        if ($family) {


            $f = [
                'owner_id' => $family->user_id,
                'family_name' => $family->name,
                'max_num' => $family->num,
                'img' => $family->image,
                // Active members + the owner — same definition as FamilyResource.
                'num_of_members' => ($family->members_count
                    ?? $family->members()->count()) + 1,
                'level' => $family->level,
                'top_stars' => [],

            ];
        }


        $agency_joined = $this->agency;

        if ($agency_joined) {


            $owner = $agency_joined->app_owner_id == $this->id
                ? new \stdClass()
                : new ShortUserResource($agency_joined->owner);

            $agency_joined = [
                'id' => $agency_joined->id,
                'name' => $agency_joined->name,
                'status' => $agency_joined->status,
                'image' => $agency_joined->img,
                // Mirrors UserResource::formatAgency — real member count.
                'member_count' => $agency_joined->members_count
                    ?? $agency_joined->members()->count(),
                'owner' => $owner,
                'top_stars' => [],
            ];
        } else {
            $agency_joined = (object)[];
        }

        $pass_status = false;
        $now_room = @$this->room;
        if ($now_room && $now_room->room_pass) {
            $pass_status = true;
        }

        $admin = $this->agencyUserJob;
        $owner = $this->ownAgency;



        // Common::getUserDress($this->id, $this->dress_3, 6, 'img1', true);

        $show_user_setting = $this->userSetting;
        if ($show_user_setting == null) {
            $show_user_setting = new UserSetting([
                'user_id' => $this->id,
                'show_git' => 1,
                'show_intro' => 1,
                'show_banner' => 1,
                'show_invite_code' => 1,
            ]);
        }

        $achievement_images = [];
        /*if ($this->medals) {
            foreach ($this->medals as $medal) {
                if ($medal->achievementLevel) {
                    $achievement_images[] = $medal->achievementLevel->valid_image;
                }
            }
        }*/
        $counters = [];

        if ($request->show_counter) {
            $userCounterServices = new \Modules\Public\Http\Services\UserCounterServices();

            $types = ['system_message', 'official_message', 'followers', 'followeds', 'friend', 'visitor', 'mybag', 'mall'];

            $counters = $userCounterServices->getUserCountsV2($this->resource, $types);

            $counters['bag'] = $counters['mybag'] ?? 0;

            $counters['message'] = $userCounterServices->getCountByType($this->resource, 'message');
        }

        $ownerRoom = $this->ownerAudioRoom;
        $pks = !is_null($ownerRoom?->id) ? $this->getRoomTwoLastPk($ownerRoom->id) : null;
        /**@var User $this
         * @var Room $ownerRoom*/

        $uuid = @$this->uuid;
        $wabble = UserPackHelper::getWare($this->resource, 12);
        $isStopInvitationValid = null;

        if (self::isStopInvitationValid()) {
            $isStopInvitationValid = true;
        }
        $vipData = UserPackHelper::getVipData($this->resource);
        $profileVisitorsCount = $this->profileVisits()->count();
        $nextSenderLevelInfo = $this->next_sender_level_info;
        $data = [
            'id' => @$this->id,
            'notification_id' => @$this->notification_id ?: "",
            'name' => @$this->name ?: 'user' . ' ' . '#' . $uuid,
            'phone' => (string)@$this->phone ?: '',
            'firebase_uuid' => (string)@$this->firebase_uuid ?: '',

            //'manger' => new MangerTypeResource(@$this->manager),
            'frame' => UserPackHelper::getFrameImage($this->resource),
            'frame_id' => UserPackHelper::getFrameId($this->resource),
            'frame_type' => UserPackHelper::getFrameType($this->resource),

            'intro' => UserPackHelper::getIntroFile($this->resource),
            'intro_type' => UserPackHelper::getIntroType($this->resource),
            'intro_id' => UserPackHelper::getIntroId($this->resource),
            //Bubble
            'bubble' => UserPackHelper::getBubbleImage($this->resource),
            'bubble_id' => UserPackHelper::getBubbleId($this->resource),
            //endBubble

            //wabble
            'wabble' => $wabble ? new GeneralUserWareResource($wabble) : (object)[],
            'wabble_id' => UserPackHelper::getWabbleId($this->resource),
            //endWabble

            //ColorName
            'has_color_name'       => (bool)UserPackHelper::getColorName($this->resource),
            'vip' =>  [
                'id'             => $this->UserVip?->id,
                'level'          => $this->UserVip?->level,
                'img_old'        => $this->UserVip?->OVip->img ?? '',
                'vip_upload_gif' => UserPackHelper::hasPack($this->resource, 22),
                'vip_img'        => UserPackHelper::getVipIcon($this->resource),
                'colored_name'   => UserPackHelper::getColorName($this->resource),
            ],
            //EndColorName
            //AntiBan
            'has_anti_ban'       => UserPackHelper::hasAntBan($this->resource),
            //EndAntiBan

            //Anonymous
            'anonymous' => $this->packs->where('type', 17)->count() >= 1,
            //EndAnonymous

            'country_name' => $this->country ? (app()->getLocale() == 'en' ? $this->country->e_name : $this->country->name) : '',
            'country_hidden' => UserPackHelper::hasHideCountry($this->resource),

            'profile_frame_id' => UserPackHelper::getProfileFrameId($this->resource),

            'is_first' => (bool)$this->is_points_first,
            'is_agency_request' => $this->agencyJoinRequest()->where('status', '!=', 2)->exists(),
            'has_room' => (bool)$ownerRoom,
            'google_bind' => (bool)@$this->google_id,

            'room' => [
                "id" => @$ownerRoom->id ?? 0,
                "owner_uuid" => $uuid,
                "room_name" => @$ownerRoom->room_name ?? '',
                "room_cover" => @$ownerRoom->room_cover ?? '',
                "room_background" => @$ownerRoom->final_room_image ?? '',
                "mode" => @$ownerRoom->mode ?? 0,
                'giftPrice' => @$ownerRoom->session_string ?? "0",
                "is_pk"               => (@$pks[0]) && @$pks[0]->end_at >= now() ? @$pks[0]->status : 0,
                "show_pk"             => @$ownerRoom->is_show_pk ?? 0,
                'password_status'     => !(@$ownerRoom->room_pass == ""),
                'type-number'                => @$ownerRoom->room_type ?? 0,
                'type' => @$ownerRoom->myType ?: new \stdClass(),

            ],
            'phone_bind' => (bool)@$this->phone,

            'image' => @$this->UserVip->OVip->img,
            'family_id' => $f == null ? null : @$this->family_id,
            'uuid' => $uuid,
            'special_color'    => @$this->color_id ?? '',
            'bio' => @$this->bio ?: '',
            'profile_visitors' => $profileVisitorsCount,
            'total_views' => $this->getTotalViews($profileVisitorsCount),
            'number_of_fans'       => $this->number_of_fans,
            'number_of_followings' => $this->number_of_followings,
            'number_of_friends'    => $this->number_of_friends,
            'profile' => $this->profile ? new ProfileResource($this->profile) : null,
            'level' => [
                'receiver_img' => $this->receiverLevel?->img ?? '',
                'exp_receiver' => $this->receiverLevel?->exp ?? 0,
                'sender_img'   => $this->senderLevel?->img ?? '',
                'sender_level' => intval($this->senderLevel?->level),
                'next_sender_level' => intval($nextSenderLevelInfo['next_level'] ?? 0),
                'remaining_to_next_level' => floatval($nextSenderLevelInfo['remaining_exp_ratio'] ?? 0.0),
                'sender_per' => Common::userLevelPer($this->resource),

            ],
            'charge_level' =>  [
                'current_level'  => $this->chargeLevel->level ?? 0,
                'current_exp'    => $this->chargeLevel->exp ?? 0,
                'current_img'    => $this->chargeLevel->img ?? '',
                'next_level'     =>  0,
                'next_exp'       =>  0,
                'next_img'       =>  '',
                'remaining'         =>  0,
                'progress'          =>  0,
            ],
            'game_available' => (bool)UserHandling::chickLevelToPlay($this->resource),

            'family_data' => $f,
            'agency' => $agency_joined,
            'Last_seen' => Carbon::createFromTimestamp($this->online_time)->format('d/m/y H:i'),
            'type_user' => intval(@$this->type_user) ?: 0,
            'user_jobs' => $this->jobs,

            //            'country' => $this->country ?? null,
            'country' => $this->country ? [
                'id' => $this->country->id,
                'name' => $this->country->name,
                'flag' => $this->country->flag,
                'language' => $this->country->language,
                'e_name' => $this->country->e_name,
                'phone_code' => $this->country->phone_code,
                'iso' => substr($this->country->iso, 0, 2),
            ] : null,

            'gender' => @$this->gender == 1 ? "custom_image/male.png" : "custom_image/female.png",
            "change_room_effect" => new ShowUserSettingResource(@$show_user_setting),
            'user_agency_status' => $owner ? 2 : ($admin ? 1 : 3),
            'achievement_images' => $achievement_images,
            "multi_images" => $this->images?->select('id', "img"),
            "family_price" =>  Common::getConfig('family_price') ?? 0,
            'image_color'          => @$this->color_image,
            $this->mergeWhen($request->show_counter == true, [
                'unread_counter'       =>  $counters,
            ]),
            'company_number' => Common::getConfig('company_number'),
            'special_id'          =>  @$this->specialId?->ware?->id ?? 0,
            'special_id_image'          =>  @$this->specialId?->ware?->show_img ?? "",
            'new_gift'          => (bool)$this->new_gift,
            'show_invite_code' => !$isStopInvitationValid && ($this->userSetting?->show_invite_code ?? false),
            'wallet' => $this->wallet?->value ?? 0,
            'user_types' => $this->user_types,

            "shipping-agency" => $this->shippingAgency ? [
                "id" => $this->shippingAgency->id,
                "name" => $this->shippingAgency->name ?? '',
                "image" => $this->shippingAgency->img ?? '',
                "complete-transactions" =>  0,
                "top_stars" => (object)[],

            ] : null,

        ];

//        $data['auth_token'] = $this->auth_token;
        if (isset($this->is_mic)) {
            $data['is_mic'] = $this->is_mic;
        }
        if ($this->first_login_date) {
            $data['first_login_date'] = $this->first_login_date->format('Y-m-d H:i:s');
        }
        if ($pass_status) {
            $data['pass_status'] = $pass_status;
        }

        return $data;
    }
    /**
     * Total views shown on the "me" tab = reels views + moments views + profile views.
     * Computed cheaply from denormalized per-row counters (reals.views_count,
     * moment.views_count) plus the existing profile-visitors signal.
     */
    private function getTotalViews(int $profileViews): int
    {
        $reelsViews = (int) DB::table('reals')->where('user_id', $this->id)->sum('views_count');
        $momentsViews = (int) DB::table('moment')->where('user_id', $this->id)->sum('views_count');

        return $reelsViews + $momentsViews + $profileViews;
    }

    private function getRoomTwoLastPk(int $roomId)
    {
        return Pk::query()
            ->where('room_id', $roomId)
            ->orderByDesc('created_at')
            ->limit(2)
            ->get();
    }

    // public function senderPer($user)
    // {
    //     $gold_level             = $user->total_sender_level;
    //     $vipsData = Vip::collectionBuilder()->get();
    //     $expPercentages  = Config::get('exp_percentages') ?? [0, 0];

    //     $nextGoldData = self::getNextLevelDataFromCache(2, $gold_level, $vipsData);

    //     $diamondSend             = $user->total_sender_diamonds;

    //     $senderNum        = floor($diamondSend  * $expPercentages['exp_sender_percentage']);

    //     $next_gold_num = $nextGoldData['next_exp'];

    //     $current_gold_num = self::getCurrentLevelFromCache(2, $gold_level, 'exp', $vipsData);
    //     $st = (int)$next_gold_num - (int)($current_gold_num);
    //     $sc = (int)$senderNum - (int)($current_gold_num);



    //     if ($st > 0 && ($sc / $st) < 1 && ($sc / $st) > 0) {
    //         $data['sender_per'] = (float)($sc / $st);
    //     } else {
    //         $data['sender_per'] = (float)0.00;
    //     }
    // }



    public function getUserDress($type, $dress, $item = 'img1')
    {
        $pack = $this->packs->where('is_used', 1)
            ->where('type', $type)
            ->where('target_id', $dress)
            ->first();
        return $pack && $pack->ware ? $pack->ware->{$item} : '';
    }

    // private static function isStopInvitationValid()
    // {
    //     return settings()->get('stop_invite_code');
    // }

    private static function isStopInvitationValid()
    {
        return getSettingCash('invite_code') ?? 0;
    }
}
