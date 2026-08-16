<?php

namespace App\Tik\Services;


use App\Http\Services\RoomService;
use App\Models\Cp;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Common;
use App\Models\UserGift;
use App\Helpers\CacheHelper;
use App\Enums\GiftSourceType;
use GuzzleHttp\Promise\Utils;
use App\Enums\UserCoinLogType;
use App\Events\GiftBannerEvent;
use App\Tik\DTO\ReceiverGiftDTO;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdatePkAndSendToZigo;
use Illuminate\Support\Facades\Log;
use App\Classes\Gifts\SendGiftService;
use Modules\CP\Http\Services\CpService;
use App\Tik\Repositories\GiftRepository;
use App\Tik\Repositories\RoomRepository;
use App\Tik\Repositories\UserRepository;
use App\Tik\Repositories\GiftLogRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Classes\Gifts\UpdateUserWhenSendGift;
use GuzzleHttp\Exception\BadResponseException;
use App\Repositories\Room\RoomTopUsersRepository;
use Modules\RoomBoom\Services\NewRoomBoomGiftService;
use Modules\Public\Http\Services\UpgradeRoomLevelServices;


class GiftLogService
{

    public function __construct(
        private readonly GiftRepository $giftRepository,
        private readonly RoomTopUsersRepository $roomTopUsersRepository,
        private readonly RoomRepository $repository,
        private readonly UserRepository $UserRepository,
        private readonly GiftLogRepository $giftLogRepository,
    ) {}


    /**
     * @throws \Throwable
     */
    public function sendGift($request, UpdateUserWhenSendGift $updateUserWhenSendGift)
    {
        return DB::transaction(function () use ($request, $updateUserWhenSendGift) {
            // room_id 1
            // owner id 1
            $data = $request;
            $user = $request->user();
            $userId = $user->id;
            $ownerId = @$data['owner_id'];
            $roomId = @$data['room_id'];
            $giftId = $data['id'];
            $number = $data['num'];
            $type = $data['type'];
            $sourceType = GiftSourceType::fromType($type)->value;


            $gift = \Illuminate\Support\Facades\Cache::remember("gift_{$giftId}", 3600, function () use ($giftId) {
                return $this->giftRepository->findById($giftId);
            });
            if (!$gift)
                return throw new \Exception('Gift does not exist or has been removed');

            // Lucky gifts (type 6) must NEVER ride the normal gift path: this path
            // credits receivers with normal-gift percentage semantics (no draw, no
            // engine receiver cut) — a lucky id sent here pays receivers far more
            // than the configured cut. Lucky goes through sendLuckyGiftV2 only.
            if ((int) $gift->type === 6) {
                throw new \Exception(__('api_responses.giftNotFound'));
            }

            // receivers ids
            $receiversIds = explode(',', $data['toUid']);
            $numberOfGift = $number * count($receiversIds);
            $totalPrice = $gift->price * $numberOfGift;
            $totalPriceForOnlyReceiver = $gift->price * $number;
            // if user didn't have inf coins throw exception
            $check = $this->checkGiftAvailability($user, $gift, $numberOfGift, $type, $totalPrice);
            if ($check) {
                return $check;
            }

            // Get Room Data
            if (isset($ownerId)) {
                $room = $this->repository->findTypeUserRoom($ownerId, selectRow: 'id,uid,room_name,room_cover,play_num,hot,room_pass,session,microphone,charizma_status,type,total_diamond,level,level_id');
            } else {
                $room = $this->repository->findUserRoomById($roomId, 'id,uid,room_visitor,play_num,room_cover,room_name,hot,room_pass,session,microphone,charizma_status,type,total_diamond,level,level_id');
                $ownerId = $room?->uid;
            }

            // Validation if no room
            if (!$room)
                throw new \Exception('room does not exist');

            // validation if this gift vip < user vip then throw Exception
            /** @var User $user*/
            $vip_level = $user->UserVip?->level;

            $existingGiftCount = UserGift::where('user_id', $user->id)
                ->where('gift_id', $gift->id)
                ->where(function ($query) {
                    $query->where('expire', 0)
                        ->orWhereRaw('DATE_ADD(created_at, INTERVAL expire DAY) >= NOW()');
                })->first();

            if ((@$vip_level < $gift->vip_level) && ($type !== 'bag' || !$existingGiftCount || $existingGiftCount->quantity < (int)$number)) {
                throw new \Exception('vip ' . $gift->vip_level . ' to send this gift');
            }
            // get received users data
            $receivedUsers = $this->UserRepository->getUsers($receiversIds);

            //        $percentageValues = $this->getReceivedAndSanderPercentage();
            //decrement the user coins
            $sendPrice = (int) ($totalPrice);
            if ($type !== 'bag') {
                $amountBefore = $user->di;

                UserCoinLogHelper::logByType(
                    $user->id,
                    -abs($sendPrice),
                    $amountBefore,
                    UserCoinLogType::GIFT,
                    $gift?->name
                );

                $updateUserWhenSendGift->send($sendPrice, $user);
            } else {

                $updateUserWhenSendGift->sendFromBagAndRemoveGift($sendPrice, $user, $giftId, $numberOfGift);
            }

            //increase room session
            $room->enableSaving = false;
            $room->session += $totalPrice;
            $room->save();

            //update family level to the sender user

            if (is_array($receiversIds) && count($receiversIds) > 1) {
                $to_id = $receiversIds[0];
                $to = "";
                if ($room->type == "audio") {
                    $to = 'الغرفة';
                } else {
                    $to = __('live');
                }
            } else {
                $to_id = $receiversIds[0];
                $to = @$receivedUsers->first()->name;
            }

            $fromName = $user->name;
            $sendGiftServices = new SendGiftService();


            $cpIds = [];
            $cpEnableAllGifts = getCpGiftsStatus('cp_enable_all_gifts') ?? 1;

            if ($cpEnableAllGifts || ($gift->category && $gift->category->type === 'cp')) {
                $hasCp = \Illuminate\Support\Facades\Cache::remember("user_has_cp_{$user->id}", 60, function () use ($user) {
                    return Cp::where(function ($query) use ($user) {
                        $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id);
                    })->whereIn('status', [1, 4])->exists();
                });

                if ($hasCp) {
                    try {
                        $cpIds = (new CpService())->processCpWhenSendGift($user, $receivedUsers, $giftId, $totalPriceForOnlyReceiver);
                    } catch (\Exception $e) {
                    }
                }
            }

            if ($room->lastPk != null) {

                dispatch(new UpdatePkAndSendToZigo($user->id, $room->id, $receivedUsers->pluck('id')->toArray(), ($gift->price * $number), $room))
                    ->afterCommit()
                    ->onQueue('updatePk');
            }

            // Charisma is SERVER-AUTHORITATIVE: the backend owns the per-room,
            // per-receiver cumulative total in Redis (RoomCharismaStore) and ships
            // the resulting TOTAL inside the gift frame + enter-room payload; the
            // client only renders. The increment is the RECEIVED value already
            // credited (gift->price * number per receiver — the same number used for
            // gift_logs giftPrice), so there is no parallel rate and no zero-share
            // drop. Gated by charizma_status. PK still rides dispatchRoomsRedis
            // (block above), untouched.

            $realPrice = (int) ($number * $gift->price);

            $price = ceil($realPrice);
            $pk = (!is_null($room->lastPk) || !is_null($room->lastPkSession)) ? 1 : 0;
            $roomBoomUuid = $sendGiftServices->sendGift3($number, $room, $gift, $user, $receivedUsers, totalPrice: $price, isPk: $pk, cpIds: $cpIds, sourceType: $sourceType, type: $type);

            $settings = CacheHelper::cacheSettings();
            /** @var Collection $rememberForever*/
            if (gettype($settings) !== 'array') {
                $settings = $settings->pluck('value', 'key')->toArray();
            }

            $roomBoomSettings = $settings['room_boom'] ?? 1;
            if ($roomBoomSettings) {
                (new NewRoomBoomGiftService())->sendGift($room, $totalPrice, $userId);
            } else {
                $tz = getTimezone();
                $todayStart = Carbon::now($tz)->startOfDay()->copy()->setTimezone('UTC');

                $totalRoomGift = (new RoomService())->getOrCreateTotalRoomGift($room->id, $todayStart);

                $totalRoomGift->increment('current_total', $totalPrice);
            }

            $updateUserWhenSendGift->updateUsers($price, $receiversIds);

            \App\Jobs\UpdateFamilyLevelJob::dispatch($receivedUsers, $gift->price * $number)
                ->afterCommit()
                ->onQueue(getLeastBusyQueue('heavyProcessing') ?? 'default');

            if ($room->mode != '1' && $room->mode != '2') {
                \App\Jobs\UpdateRoomCoinsJob::dispatch($userId, $room->id, $totalPrice)
                    ->afterCommit()
                    ->onQueue(getLeastBusyQueue('heavyProcessing') ?? 'default');
            }


            if ($room->type == 'audio') {
                \App\Jobs\UpdateRoomLevelJob::dispatch($room->id, $totalPrice)
                    ->afterCommit()
                    ->onQueue(getLeastBusyQueue('heavyProcessing') ?? 'default');
            }
            $message = "  {$numberOfGift} x" . __('api.sendGift') . __("api.value") . "{$totalPrice} " . __('api.to') . "{$to}";



            $totalGiftPrice = $settings['total_gift_price'] ?? 2000;


            // ── In-room gift effects via UTD-Stream (restored after the legacy-provider removal
            // left the in-room send path commented out). Every gift plays its
            // animation for everyone in the room; gifts at/above the banner threshold
            // also show the in-room "expensive gift" banner. Published on a queue
            // worker (afterCommit) so the UTD-Stream HTTP call never sits on the gift
            // hot path, which is the busiest, most 504-sensitive endpoint. ──
            // Server-authoritative charisma: when charisma is ON, credit each
            // receiver's RECEIVED value (gift->price * number — the same number used
            // for gift_logs giftPrice) into the durable per-room Redis store and ship
            // the resulting per-receiver TOTAL inside the gift frame. The client
            // renders the carried total; it never accumulates. ALWAYS shipped when
            // charisma is on, even if a single increment is small.
            $receiverCharismaTotals = [];
            if ($room->charizma_status) {
                $receiverCharismaShare = (int) ($gift->price * $number);
                $byReceiver = [];
                foreach ($receiversIds as $rid) {
                    $byReceiver[(int) $rid] = $receiverCharismaShare;
                }
                $newTotals = \App\Services\RoomCharismaStore::increment((int) $room->id, $byReceiver);
                foreach ($newTotals as $rid => $total) {
                    $receiverCharismaTotals[(string) $rid] = $total;
                }
            }

            $inRoomFrames = [];
            $inRoomFrames[] = json_encode([
                'messageContent' => [
                    'message'    => 'showGifts',
                    'send_id'    => (int) $user->id,
                    'coins'      => $user->coins_string,
                    'type'       => $gift->image_type,
                    'giftType'   => $totalPrice >= $totalGiftPrice ? 'famous' : 'normal',
                    'showGift'   => $gift->show_img ?: $gift->show_img2,
                    'gift_price' => $totalPrice,
                    'giftImg'    => $gift->img,
                    'gift_id'    => $gift->id,
                    'num_gift'   => $number,
                    'plural'     => is_array($receiversIds) && count($receiversIds) > 1,
                    // Server-authoritative cumulative room charisma per receiver
                    // (floored coins) AFTER this credit — the client renders it.
                    'receiver_charisma_totals' => $receiverCharismaTotals,
                ],
            ], JSON_UNESCAPED_UNICODE);

            if ($totalPrice >= $totalGiftPrice) {
                try {
                    $gift_data = $this->giftEvent($gift, $user, $totalPrice, $receivedUsers->first(), $receiversIds, $room, $number);
                    event(new GiftBannerEvent($gift_data));
                    $inRoomFrames[] = $this->buildStreamBannerJson($gift_data);
                } catch (\Throwable $e) {
                    Log::error('gift banner dispatch failed: ' . $e->getMessage());
                }
            }

            $inRoomRoomId = $room->id;
            $inRoomSenderId = (int) $user->id;
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($inRoomRoomId, $inRoomSenderId, $inRoomFrames) {
                try {
                    dispatchJobToQueue(new \App\Jobs\SendRoomDataJob($inRoomRoomId, $inRoomSenderId, $inRoomFrames), 'heavyProcessing');
                } catch (\Throwable $e) {
                    Log::error('in-room gift effect dispatch failed: ' . $e->getMessage());
                }
            });

            return $message;
        }, attempts: 3);
    }


    public function sendTestGift($request, UpdateUserWhenSendGift $updateUserWhenSendGift)
    {
        return DB::transaction(function () use ($request, $updateUserWhenSendGift) {

            // room_id 1
            // owner id 1
            $data = $request;
            $user = User::where('id', 1206)->first();
            $userId = $user->id;
            $ownerId = @$data['owner_id'];
            $roomId = @$data['room_id'];
            $giftId = $data['id'];
            $number = $data['num'];
            $type = $data['type'];
            $sourceType = GiftSourceType::fromType($type)->value;


            $gift = \Illuminate\Support\Facades\Cache::remember("gift_{$giftId}", 3600, function () use ($giftId) {
                return $this->giftRepository->findById($giftId);
            });
            if (!$gift)
                return throw new \Exception('Gift does not exist or has been removed');

            // Lucky gifts (type 6) are blocked on the normal path (see sendGift).
            if ((int) $gift->type === 6) {
                throw new \Exception(__('api_responses.giftNotFound'));
            }

            // receivers ids
            $receiversIds = explode(',', $data['toUid']);
            $numberOfGift = $number * count($receiversIds);
            $totalPrice = $gift->price * $numberOfGift;
            $totalPriceForOnlyReceiver = $gift->price * $number;
            // if user didn't have inf coins throw exception
            $check = $this->checkGiftAvailability($user, $gift, $numberOfGift, $type, $totalPrice);
            if ($check) {
                return $check;
            }

            // Get Room Data
            if (isset($ownerId)) {
                $room = $this->repository->findTypeUserRoom($ownerId, selectRow: 'id,uid,room_name,room_cover,play_num,hot,room_pass,session,microphone,charizma_status,type,total_diamond,level,level_id');
            } else {
                $room = $this->repository->findUserRoomById($roomId, 'id,uid,room_visitor,play_num,room_cover,room_name,hot,room_pass,session,microphone,charizma_status,type,total_diamond,level,level_id');
                $ownerId = $room?->uid;
            }

            // Validation if no room
            if (!$room)
                throw new \Exception('room does not exist');

            // validation if this gift vip < user vip then throw Exception
            /** @var User $user*/
            $vip_level = $user->UserVip?->level;
            if (@$vip_level < $gift->vip_level)
                throw new \Exception('vip ' . $gift->vip_level . ' to send this gift');

            // get received users data
            $receivedUsers = $this->UserRepository->getUsers($receiversIds);

            //        $percentageValues = $this->getReceivedAndSanderPercentage();
            //decrement the user coins
            $sendPrice = (int) ($totalPrice);
            if ($type !== 'bag') {
                $amountBefore = $user->di;

                UserCoinLogHelper::logByType(
                    $user->id,
                    -abs($sendPrice),
                    $amountBefore,
                    UserCoinLogType::GIFT,
                    $gift?->name
                );

                $updateUserWhenSendGift->send($sendPrice, $user);
            } else {

                $updateUserWhenSendGift->sendFromBagAndRemoveGift($sendPrice, $user, $giftId, $numberOfGift);
            }

            //increase room session
            $room->enableSaving = false;
            $room->session += $totalPrice;
            $room->save();

            //update family level to the sender user

            if (is_array($receiversIds) && count($receiversIds) > 1) {
                $to_id = $receiversIds[0];
                $to = "";
                if ($room->type == "audio") {
                    $to = 'الغرفة';
                } else {
                    $to = __('live');
                }
            } else {
                $to_id = $receiversIds[0];
                $to = @$receivedUsers->first()->name;
            }

            $fromName = $user->name;
            $sendGiftServices = new SendGiftService();


            $cpIds = [];
            $cpEnableAllGifts = getCpGiftsStatus('cp_enable_all_gifts') ?? 1;

            if ($cpEnableAllGifts || ($gift->category && $gift->category->type === 'cp')) {
                $hasCp = \Illuminate\Support\Facades\Cache::remember("user_has_cp_{$user->id}", 60, function () use ($user) {
                    return Cp::where(function ($query) use ($user) {
                        $query->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id);
                    })->whereIn('status', [1, 4])->exists();
                });

                if ($hasCp) {
                    try {
                        $cpIds = (new CpService())->processCpWhenSendGift($user, $receivedUsers, $giftId, $totalPriceForOnlyReceiver);
                    } catch (\Exception $e) {
                    }
                }
            }

            if ($room->lastPk != null) {

                dispatch(new UpdatePkAndSendToZigo($user->id, $room->id, $receivedUsers->pluck('id')->toArray(), ($gift->price * $number), $room))
                    ->afterCommit()
                    ->onQueue('updatePk');
            }

            // Charisma is client-side (owner decision): no backend charisma write.
            // PK still rides dispatchRoomsRedis (block above), untouched.

            $realPrice = (int) ($number * $gift->price);

            $price = ceil($realPrice);

            $pk = (!is_null($room->lastPk) || !is_null($room->lastPkSession)) ? 1 : 0;
            $roomBoomUuid = $sendGiftServices->sendGift3($number, $room, $gift, $user, $receivedUsers, totalPrice: $price, isPk: $pk, cpIds: $cpIds, sourceType: $sourceType, type: $type);

            $settings = CacheHelper::cacheSettings();
            /** @var Collection $rememberForever*/
            if (gettype($settings) !== 'array') {
                $settings = $settings->pluck('value', 'key')->toArray();
            }

            $roomBoomSettings = $settings['room_boom'] ?? 1;
            if ($roomBoomSettings) {
                (new NewRoomBoomGiftService())->sendGift($room, $totalPrice, $userId);
            } else {
                $tz = getTimezone();
                $todayStart = Carbon::now($tz)->startOfDay()->copy()->setTimezone('UTC');

                $totalRoomGift = (new RoomService())->getOrCreateTotalRoomGift($room->id, $todayStart);

                $totalRoomGift->increment('current_total', $totalPrice);
            }

            $updateUserWhenSendGift->updateUsers($price, $receiversIds);

            \App\Jobs\UpdateFamilyLevelJob::dispatch($receivedUsers, $gift->price * $number)
                ->afterCommit()
                ->onQueue(getLeastBusyQueue('heavyProcessing') ?? 'default');

            if ($room->mode != '1' && $room->mode != '2') {
                \App\Jobs\UpdateRoomCoinsJob::dispatch($userId, $room->id, $totalPrice)
                    ->afterCommit()
                    ->onQueue(getLeastBusyQueue('heavyProcessing') ?? 'default');
            }


            if ($room->type == 'audio') {
                \App\Jobs\UpdateRoomLevelJob::dispatch($room->id, $totalPrice)
                    ->afterCommit()
                    ->onQueue(getLeastBusyQueue('heavyProcessing') ?? 'default');
            }
            $message = "  {$numberOfGift} x" . __('api.sendGift') . __("api.value") . "{$totalPrice} " . __('api.to') . "{$to}";



            $totalGiftPrice = $settings['total_gift_price'] ?? 2000;


            if ($totalPrice >= $totalGiftPrice) {
                try {
                    $gift_data = $this->giftEvent($gift, $user, $totalPrice, $receivedUsers->first(), $receiversIds, $room, $number);
                    event(new GiftBannerEvent($gift_data));
                } catch (\Throwable $e) {
                    Log::error('gift banner dispatch failed: ' . $e->getMessage());
                }
            }

            return $message;
        }, attempts: 3);
    }

    /**
     * @throws \Throwable
     */
    private function checkGiftAvailability($user, $gift, $number, $type, $totalPrice)
    {
        if ($type == 'bag') {

            $existingGiftCount = UserGift::where('user_id', $user->id)
                ->where('gift_id', $gift->id)
                ->where(function ($query) {
                    $query->where('expire', 0)
                        ->orWhereRaw('DATE_ADD(created_at, INTERVAL expire DAY) >= NOW()');
                })->first();


            throw_if((!$existingGiftCount || $existingGiftCount->quantity < $number), \Exception::class, 'Not enough gifts in your bag');


            return null;
        }

        throw_if(
            $user->di < $totalPrice,
            \Exception::class,
            'Insufficient balance, please go to recharge!'
        );

        return null;
    }

    private function gift_event($gift, $receivedUsers, $user, $totalPrice, $receivedUser, $receiversIds, $room, $ownerId, $number)
    {
        $gift_data = [
            'show_gift' => $gift->show_img ?: $gift->show_img2,
            'gift_img' => $gift->img,
            'gift_id' => $gift->id,
            'sender_id' => (int) $user->id,
            'receiver_id' => @(int) $receivedUser->id,
            'num_gift' => $totalPrice,
            "plural" => is_array($receiversIds) && count($receiversIds) > 1,
            'room_session' => $room->session_string,
            'is_password' => (bool) (@$room->room_pass),
            'room_uuid' => $room->owner?->uuid ?: 0,
            'room_id' => (string) ($room->id ?: 0),
            'room_owner_id' => $room->uid ?: 0,
            'room_name' => $room->room_name ?: '',
            "room_mode" => $room->mode,
            "room_cover" => $room->room_cover ?? '',
            "room_background" => $room->final_room_image ?? '',
            'from_name' => $user->name,
            'to_name' => @$receivedUser->name,
            'gift_price' => $gift->price,
            'owner_id' => $ownerId,
            'number' => $number,
            'coins' => $user->coins_string,
            'gift_image_type' => $gift->image_type,
            's_vip_level' => @$user->userVip->level ?? 0,
            's_image' => @$user->profile->avatar ?? '',
            's_name' => @$user->name ?? '',
            's_sender_level' => @$user->total_sender_level,
            's_receiver_level' => @$user->total_received_level,
            'r_vip_level' => @$receivedUser->userVip->level ?? 0,
            'r_name' => @$receivedUser->name ?? '',
            'r_image' => @$receivedUser->profile->avatar ?? '',
            'r_sender_level' => @$receivedUser->total_received_level,
            'r_receiver_level' => @$receivedUser->total_sender_level,
            'room_type' => @$room->type,
        ];

        event(new GiftBannerEvent($gift_data));
    }


    public function giftEvent($gift, $user, $totalPrice, $receivedUser, $receiversIds, $room, $number)
    {

        $receiverGiftDTO = (count($receiversIds) > 1) ? ReceiverGiftDTO::fromRoom($room) : ReceiverGiftDTO::fromUser($receivedUser);
        $gift_data = [
            'show_gift' => $gift->show_img ?: $gift->show_img2,
            'gift_img' => $gift->img,
            'gift_id' => $gift->id,
            'sender_id' => (int) $user->id,
            'receiver_id' => $receiverGiftDTO->id,
            'num_gift' => $totalPrice,
            "plural" => is_array($receiversIds) && count($receiversIds) > 1,
            'room_session' => $room->session_string,
            'is_password' => (bool) (@$room->room_pass),
            'room_uuid' => $room->owner?->uuid ?: 0,
            'room_id' => (string) ($room->id ?: 0),
            'room_owner_id' => $room->uid ?: 0,
            'room_name' => $room->room_name ?: '',
            "room_mode" => $room->mode,
            "room_cover" => $room->room_cover ?? '',
            "room_background" => $room->final_room_image ?? '',
            'from_name' => $user->name,
            'to_name' => $receiverGiftDTO->name,
            'gift_price' => $gift->price,
            'owner_id' => $room->uid,
            'number' => $number,
            'coins' => $user->coins_string,
            'gift_image_type' => $gift->image_type,
            's_vip_level' => @$user->userVip->level ?? 0,
            's_image' => @$user->profile->avatar ?? '',
            's_name' => @$user->name ?? '',
            's_sender_level' => @$user->total_sender_level,
            's_receiver_level' => @$user->total_received_level,
            'r_vip_level' => $receiverGiftDTO->vipLevel,
            'r_name' => $receiverGiftDTO->name ?? '',
            'r_image' => $receiverGiftDTO->avatar ?? '',
            'r_sender_level' => $receiverGiftDTO->senderLevel,
            'r_receiver_level' => $receiverGiftDTO->receiverLevel,
            'room_type' => @$room->type,
        ];

        return $gift_data;
    }

    /**
     * يبني رسالة UTD-Stream لبانر الهدية الغالية بعقد الفورمات الذي يتوقعه العميل:
     * تغليف تحت مفتاح "gift" + message = "showBanner".
     *
     * payload نحيف يحوي فقط الحقول الـ14 التي يستهلكها العميل فعلياً (الـ widget
     * في app.dart + الضغط _giftTap)، للبقاء تحت حد SendCustomCommand (1024 بايت).
     * gift_data الكاملة (34 حقلاً + روابط) تتجاوز الحد وتُرفض بـ error 50012.
     * JSON_UNESCAPED_UNICODE يُبقي العربية UTF-8 (2-3 بايت/حرف) بدل \uXXXX (6 بايت).
     */
    private function buildStreamBannerJson(array $giftData): string
    {
        $slim = [
            'num_gift'        => $giftData['num_gift'] ?? 0,
            's_image'         => $giftData['s_image'] ?? '',
            'r_image'         => $giftData['r_image'] ?? '',
            'gift_img'        => $giftData['gift_img'] ?? '',
            'is_password'     => $giftData['is_password'] ?? false,
            'room_id'         => $giftData['room_id'] ?? '',
            'room_owner_id'   => $giftData['room_owner_id'] ?? 0,
            'room_type'       => $giftData['room_type'] ?? '',
            'gift_price'      => $giftData['gift_price'] ?? 0,
            'room_name'       => $giftData['room_name'] ?? '',
            'room_cover'      => $giftData['room_cover'] ?? '',
            'room_background' => $giftData['room_background'] ?? '',
            'room_mode'       => $giftData['room_mode'] ?? '',
            'room_uuid'       => $giftData['room_uuid'] ?? '',
        ];

        return json_encode([
            'messageContent' => [
                'message' => 'showBanner',
                'gift'    => $slim,
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    public function sendToStream($gift, $to_id, $totalPrice, $receiversIds, $room, ?string $toName, $ownerId, $number, $user, $firstReceiver, ?bool $isToStream = false): array
    {
        $streamData = collect(
            [
                'show_gift' => $gift->show_img ?: $gift->show_img2,
                'gift_img' => $gift->img,
                'gift_id' => $gift->id,
                'sender_id' => (int) $user->id,
                'receiver_id' => (int) $to_id,
                'num_gift' => $totalPrice,
                "plural" => is_array($receiversIds) && count($receiversIds) > 1,
                'room_session' => $room->session_string,
                'is_password' => (bool) (@$room->room_pass),
                'room_id' => $room->id,
                'from_name' => $user->name,
                'to_name' => $toName,
                'gift_price' => $totalPrice,
                'owner_id' => $ownerId,
                'number' => $number,
                'coins' => $user->coins_string,
                'gift_image_type' => $gift->image_type,
                'room_type' => $room->type ?? 'audio',

            ]
        );


        if ($totalPrice >= 2000) {
            $levels = [
                $user->total_sender_level,
                $user->total_received_level,
                $firstReceiver->total_received_level,
                $firstReceiver->total_sender_level,
            ];
            /*$levels     = Common::getLevels($levels);
            $senderLevels = $levels->where('type', '=',2);
            $receiverLevels = $levels->where('type', '=',1);*/
            $values = [
                's_vip_level' => @$user->userVip->level ?? 0,
                's_image' => @$user->profile->avatar ?? '',
                's_name' => @$user->name ?? '',
                's_sender_level' => @$user->total_sender_level,
                's_receiver_level' => @$user->total_received_level,
                'r_vip_level' => @$firstReceiver->userVip->level ?? 0,
                'r_name' => @$firstReceiver->name ?? '',
                'r_image' => @$firstReceiver->profile->avatar ?? '',
                'r_sender_level' => @$firstReceiver->total_received_level,
                'r_receiver_level' => @$firstReceiver->total_sender_level,
            ];
            $streamData = $streamData->merge($values);
        }

        //        dispatch(new SendGiftToZegoJob($streamData, $totalPrice, ($request->to_zego == 1)))->onQueue('sendGiftToZigo');
        /* $startTime = microtime(true);*/
        return $this->sendStreamGifts($streamData, $totalPrice, ($isToStream));
    }

    public function sendStreamGifts($streamData, $totalPrice, $isToStream): array
    {
        //        Common::sendToStream_2('SendBroadcastMessage', $streamData['room_id'], $streamData['sender_id'], $streamData['from_name'], "  {$streamData['number']} x ارسل هدية  " . " قيمتها {$streamData['gift_price']} " . " الى {$streamData['to_name']}");
        if ($isToStream) {
            $d = [
                "messageContent" => [
                    "message" => "showGifts",
                    "showGift" => $streamData['show_gift'],
                    'giftImg' => $streamData['gift_img'],
                    'gift_id' => $streamData['gift_id'],
                    'send_id' => $streamData['sender_id'],
                    'receiver_id' => $streamData['receiver_id'],
                    'isExpensive' => $totalPrice >= 2000,
                    'num_gift' => $streamData['number'],
                    "plural" => $streamData['plural'],
                    'gift_price' => $totalPrice, // $streamData['room_session'],,
                    'giftTP' => $totalPrice,
                    'coins' => @$streamData['coins'] ?? '0',
                    'type' => @$streamData['gift_image_type'] ?? 'mp4',

                ]
            ];
            $json = json_encode($d);
            $jsons[] = $json;
        }

        //            Common::sendToStream('SendCustomCommand', $streamData['room_id'], $streamData['sender_id'], $json);
        if ($totalPrice >= 2000) {
            $d = [
                "messageContent" => [
                    "msg" => "SHB",
                    'sv' => $streamData['s_vip_level'],
                    'si' => $streamData['s_image'],
                    'sn' => $streamData['s_name'],
                    'ssl' => $streamData['s_sender_level'],
                    'srl' => $streamData['s_receiver_level'],
                    'rv' => $streamData['r_vip_level'],
                    'rn' => $streamData['r_name'],
                    'ri' => $streamData['r_image'],
                    'rsl' => $streamData['r_sender_level'],
                    'rrl' => $streamData['r_receiver_level'],
                    'oId' => (int) $streamData['owner_id'],
                    'isPass' => $streamData['is_password'],
                    'GTP' => $totalPrice,

                ]
            ];
            $json = json_encode($d);

            //                Common::sendToStream('SendCustomCommand', $streamData['room_id'], $streamData['sender_id'], $json);

            //                dispatchJobToQueue(new AllOpeningRoomsZegoRequest($json, $streamData['sender_id'], $streamData['room_id']), 'heavyProcessing');
        }
        return @$jsons ?? [];
    }

    public function updateRoomCoinsToUser($userId, $room, $totalPrice): void
    {
        $topUser = $this->roomTopUsersRepository->findOrCreate($room->id, $userId);
        $topUser->coins += $totalPrice;
        $topUser->save();
    }

    public function userGiftIfo($id, $type, $startDate, $endDate, $perPage, $page)
    {
        return $this->giftLogRepository->userGiftInfo($id, $type, $startDate, $endDate, $perPage, $page);
    }
}
