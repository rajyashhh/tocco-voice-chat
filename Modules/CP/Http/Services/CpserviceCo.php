<?php

namespace Modules\CP\Http\Services;

use App\Models\User;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Modules\Chat\Events\Chat;
use App\Enums\UserCoinLogType;
use Modules\CP\Enums\CpStatus;
use Modules\Chat\Events\OpenChat;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Entities\ChatRoom;
use Modules\CP\Entities\Cp;
use Modules\CP\Entities\CpRelation;
use Modules\Chat\Events\Conversation;
use Modules\Chat\Entities\ChatMessage;
use Modules\CP\Transformers\CpListResource;
use Modules\CP\Transformers\RankingResource;
use Modules\CP\Http\Resources\CpsUserResource;
use Modules\CP\Transformers\RequestCpResource;
use Modules\CP\Http\Resources\CpsUserResourceV2;
use Modules\Chat\Http\Resources\ChatMessageResource;
use Modules\Chat\Http\Resources\ChatRoomResource;
use Modules\Chat\Http\Services\NextServerSeqService;
use Modules\CP\Repositories\CpRepository as RepositoriesCpRepository;

class CpserviceCo
{
    protected $cpRepository;

    public function __construct(RepositoriesCpRepository $cpRepository)
    {
        $this->cpRepository = $cpRepository;
    }

    public function makeRequestCp($request, $user)
    {
        $cpRelation = $this->cpRepository->getCpRelationById($request->cp_relation_id);
        if (!$cpRelation) {
            return Common::apiResponse(0, __('There is no CP relation.'));
        }

        if ($cpRelation->type == 'solution') {
            $findRelationBetweenUsers = $this->cpRepository->findCpBetweenUsers($user->id, $request->user_id);
            if (!$findRelationBetweenUsers) {
                return Common::apiResponse(0, __('There is no CP relation between the users.'));
            }
        }

        if ($cpRelation) {
            $existingCpOne = $this->cpRepository->checkExistingCpSendingOne($user->id, $cpRelation->id);
            $existingCptwo = $this->cpRepository->checkExistingCpSendingTwo($request->user_id, $cpRelation->id);

            if ($existingCpOne && $existingCptwo) {

                return Common::apiResponse(0, __('You have already sent/received a CP request before.'));
            }
        }
        $existingCp = $this->cpRepository->checkExistingCp($user->id, $request->user_id);
        if ($existingCp && $existingCp->status == CpStatus::PENDING->value && $cpRelation->type != 'solution') {
            return Common::apiResponse(0, __("You have already sent/received a CP request before."));
        }

        if ($existingCp && in_array($existingCp->status, [CpStatus::ACTIVE->value, CpStatus::RESTORED->value]) && $cpRelation->type != 'solution') {
            return Common::apiResponse(0, __('You are already in a relation with this user.'));
        }

        if ($cpRelation->relations_number == 0) {
            $existing = $this->cpRepository
                ->getCpsByUserAndRelation($user->id, $cpRelation->id)
                ->whereIn('status', [CpStatus::PENDING->value, CpStatus::ACTIVE->value, CpStatus::RESTORED->value])
                ->first();

            if ($existing) {
                return Common::apiResponse(0, __('You can only send this relation to one user only.'));
            }
        }

        $cpCount = $this->cpRepository->getCpCount($user->id);
        if ($cpCount >= 15) {
            return Common::apiResponse(0, __('You have exceeded the allowed number of requests!'));
        }

        if ($cpRelation->cp_one == 1) {
            $existingCpOne = $this->cpRepository->checkExistingCpOne($user->id, $cpRelation->id);
            $existingCptwo = $this->cpRepository->checkExistingCpOne($request->user_id, $cpRelation->id);

            if ($existingCpOne) {
                return Common::apiResponse(0, __('You have already sent a CP request before.'));
            }

            if ($existingCptwo) {
                return Common::apiResponse(0, __('They have already sent a CP request to you, please accept it.'));
            }
        }

        $countRequestUserOne = $this->cpRepository->countExistingCpSameRelation($user->id, $request->cp_relation_id);
        if (($cpRelation->relations_number == 0) && ($countRequestUserOne > $cpRelation->relations_number) && $cpRelation->type != 'solution') {
            return Common::apiResponse(0, __('You have exceeded the number of CP requests for this relation.'));
        }

        $otherUserCp = $this->cpRepository->checkExistingSecondUserCp($request->user_id, $request->cp_relation_id);
        if (($cpRelation->relations_number == 0) && $otherUserCp && $cpRelation->type != 'solution') {
            return Common::apiResponse(0, __('This user is already in a CP relation.'));
        }

        DB::beginTransaction();

        try {
            $userRelation = $this->cpRepository->getUserRelationAvailable($user->id, $request->cp_relation_id);

            $paidByCredit = false;
            if ($userRelation) {
                $this->cpRepository->decrementUserRelationCount($userRelation);
                $paidByCredit = true;
            } else {
                if ($user->di < $cpRelation->price) {
                    DB::rollBack();
                    return Common::apiResponse(0, __('You do not have enough coins, please recharge!'));
                }

                UserCoinLogHelper::logByType(
                    $user->id,
                    -abs($cpRelation->price),
                    $user->di,
                    UserCoinLogType::CP,
                );

                $user->di -= $cpRelation->price;
                $user->save();
            }

            $stoppedRelation = $this->cpRepository->findStoppedRelationBetweenTwoUsers($user->id, $request->user_id, $cpRelation->type);

            // While pending, `price` holds exactly the coins paid for this
            // request (0 when paid by a relation credit) so a reject can
            // refund it without over- or under-paying.
            if ($stoppedRelation && $stoppedRelation->updated_at >= now()->subMonth()) {
                $stoppedRelation->status = CpStatus::PENDING->value;
                $stoppedRelation->price = $paidByCredit ? 0 : $cpRelation->price;
                $stoppedRelation->save();
                $cp_request = $stoppedRelation;
            } else {
                $cp_request = $this->cpRepository->createCp([
                    "cp_relation_id" => $request->cp_relation_id,
                    "user_one_id"    => $user->id,
                    "user_two_id"    => $request->user_id,
                    "price"          => $paidByCredit ? 0 : $cpRelation->price,
                ]);
            }

            $chatRoom = ChatRoom::BetweenUsers($user->id, $request->user_id)->first();
            if (!$chatRoom) {
                $chatRoom = ChatRoom::create([
                    'user_id'  => $user->id,
                    'user_id2' => $request->user_id
                ]);
                $user->current_room_chat = $chatRoom->id;
                $user->save();
            }

            $user2 = User::find($request->user_id);

            $data = [
                'id'     => $cp_request->id,
                'title'  => $cpRelation->description,
                'price'  => $cpRelation->price,
                'image'  => $cpRelation->image,
                'status' => 0
            ];

            $chatMessageData = [
                'chat_room_id' => $chatRoom->id,
                'user_id'      => $user->id,
                'message'      => json_encode($data),
                'type'         => 'CP',
                'status'       => 'sent',
            ];

            if ($user2->online == 1 && $user2->current_room_chat == $chatRoom->id) {
                $chatMessageData['status'] = 'seen';
            } elseif ($user2->online == 1) {
                $chatMessageData['status'] = 'received';
            }

            $chatMessage = ChatMessage::create($chatMessageData);

            // The offline-sync fetch (SyncController::messages) only returns rows
            // WITH a server_seq; a CP message created without one is invisible in
            // the opened conversation (the chat list still shows it via the
            // broadcast), so allocate the room's atomic seq and advance the
            // last-message pointers exactly like MessageService does.
            $chatMessage->server_seq = app(NextServerSeqService::class)->next($chatRoom->id);
            $chatMessage->save();

            $chatRoom->forceFill([
                'last_message_id' => $chatMessage->id,
                'last_message_at' => $chatMessage->created_at ?? now(),
            ])->save();

            CustomNotification::makeCp($user2, $user, $cpRelation->type);

            DB::commit();

            $message_resource = new ChatMessageResource($chatMessage);
            $room_resource = new ChatRoomResource($chatRoom);

            $chatuser = ($chatRoom->user_id == $user->id) ? User::find($chatRoom->user_id2) : User::find($chatRoom->user_id);

            try {
                event(new OpenChat($room_resource->toResponse(request())->getData()->data, $chatuser, $chatRoom));
            } catch (\Throwable $th) {
                return $th->getMessage();
            }

            event(new Conversation($message_resource->toResponse(request())->getData()->data, $user2, $room_resource));
            event(new Chat($room_resource->toResponse(request())->getData()->data, $user2));

            return Common::apiResponse(1, __('request sent'));
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return Common::apiResponse(0, __('An error occurred while processing the request.'));
        }
    }



    public function getRequestCp($user)
    {
        $data = $this->cpRepository->getRequestsForUser($user->id);
        return Common::apiResponse(1, '', RequestCpResource::collection($data));
    }

    /**
     * @throws \Throwable
     */
    public function respondToRequest(Request $request)
    {
        $message = ChatMessage::find($request->message_id);
        if (!$message) {
            return Common::apiResponse(0, 'لا يوجد cp');
        }

        $decryptedData = json_decode($message->message, true) ?: [];

        $cp = $this->cpRepository->findCpById($request->cp_id);

        if (!$cp || ($cp->status != 0 && $cp->status != 5)) {
            return Common::apiResponse(0, 'لا يوجد cp');
        }

        // The message must be the CP request message of this exact cp row,
        // otherwise a valid cp_id could overwrite an arbitrary chat message.
        if ($message->type != 'CP' || ($decryptedData['id'] ?? null) != $cp->id) {
            return Common::apiResponse(0, 'هناك شئ ما خطا');
        }

        $user = $request->user();

        if ($cp->user_two_id != $user->id) {
            return Common::apiResponse(0, 'هناك شئ ما خطا');
        }

        $user2 = User::find($cp->user_one_id);

        if ($request->status == 1) {

            $countRequestUserOne = $this->cpRepository->countExistingCpSameRelationActive($cp->user_one_id,  $cp->relation->id);
            if (($cp->relation->relations_number == 0) &&
                (($countRequestUserOne > $cp->relation->relations_number)) && $cp->relation->type != 'solution'
            ) {

                return Common::apiResponse(0, ' cp لقد تخطيت طلب ');
            }

            $accepted = DB::transaction(function () use ($cp) {
                $locked = Cp::lockForUpdate()->find($cp->id);
                if (!$locked || ($locked->status != 0 && $locked->status != 5)) {
                    return false;
                }

                if ($locked->relation?->type == 'solution') {
                    $cpBetweenUsers = $this->cpRepository->findCpBetweenUsers($locked->user_one_id, $locked->user_two_id);
                    if ($cpBetweenUsers) {
                        $cpBetweenUsers->status = 3;
                        $cpBetweenUsers->save();
                    }
                }

                if ($locked->status == 5) {
                    $locked->status = 4; // restored
                    $locked->price += $locked->cpRelation->price;
                    $locked->save();
                } else {
                    $this->cpRepository->updateCpStatus($locked, 1);
                }

                return true;
            });

            if (!$accepted) {
                return Common::apiResponse(0, 'لا يوجد cp');
            }

            CustomNotification::cpAction($user2, $user, 1);
            $decryptedData['status'] = 1;
        } elseif ($request->status == 2) {

            // Reject: atomically close the request and refund the card price
            // to the sender (the buyer paid coins in makeRequestCp).
            $refund = DB::transaction(function () use ($cp) {
                $locked = Cp::lockForUpdate()->find($cp->id);
                if (!$locked || ($locked->status != 0 && $locked->status != 5)) {
                    return null;
                }

                if ($locked->status == 5) {
                    $locked->status = 3;
                    $locked->save();
                    // Pre-existing restore-reject behavior: compensate the
                    // initiator with a relation credit.
                    $this->cpRepository->updateOrCreateUserRelation($locked->user_one_id, $locked->cp_relation_id);
                    return 0.0;
                }

                $this->cpRepository->updateCpStatus($locked, 2);

                $amount = (float) $locked->price;
                if ($amount <= 0) {
                    // Request was paid with a relation credit, not coins:
                    // give the credit back instead of a coin refund.
                    $this->cpRepository->updateOrCreateUserRelation($locked->user_one_id, $locked->cp_relation_id);
                    return 0.0;
                }

                $sender = User::find($locked->user_one_id);
                if (!$sender) {
                    return 0.0;
                }

                UserCoinLogHelper::logByType(
                    $sender->id,
                    abs($amount),
                    $sender->di,
                    UserCoinLogType::CP,
                );

                User::where('id', $sender->id)->increment('di', $amount);

                return $amount;
            });

            if ($refund === null) {
                return Common::apiResponse(0, 'لا يوجد cp');
            }

            $decryptedData['status'] = 2;
            CustomNotification::cpAction($user2, $user, 2, $refund);
        }

        $message->message = json_encode($decryptedData);

        $message->save();

        return Common::apiResponse(1, 'تم الرد علي الطلب بنجاح');
    }

    public function getCpRanking()
    {
        $relationType = request("relationType") ?? 'lovely';

        $type = request("type") ?? 1;

        $data = $this->cpRepository->getCpRanking($relationType, $type);

        $first = $data->take(3);
        $second = $data->skip(3);
        $user = request()->user();
        $user->with([
            'profile:id,user_id,avatar',
            'cpsAsOne.cpRelation',
            'cpsAsTwo.cpRelation',
            'receiverLevel:id,img',
            'senderLevel:id,img',
            'packs' => fn($q) => $q->whereIn('type', [25])->where('is_used', true)->with('ware:id,value')
        ]);


        $result = [
            "firstThree" => RankingResource::collection($first),
            "remain" => RankingResource::collection($second),
            'user' => new CpsUserResourceV2($user, $relationType),
        ];

        return Common::apiResponse(1, '', $result);
    }

    public function getCpList($userId)
    {
        $data = $this->cpRepository->getCpList($userId, true);
        return Common::apiResponse(1, '', CpListResource::collection($data));
    }


    public function cpUserList($userId)
    {
        $data = $this->cpRepository->cpUserList($userId, true);
        return Common::apiResponse(1, '', CpListResource::collection($data));
    }
}
