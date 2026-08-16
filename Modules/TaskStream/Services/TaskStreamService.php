<?php

namespace Modules\TaskStream\Services;

use App\Exceptions\CValidationException;
use App\Models\User;
use App\Repositories\User\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\TaskStream\Entities\PkSession;
use Modules\TaskStream\Events\TaskStreamInvitation;
use Modules\TaskStream\Events\TaskStreamRespondInvitation;
use Modules\TaskStream\Repositories\TaskStreamInvitationRepository;
use Modules\TaskStream\Repositories\TaskStreamRepository;
use Modules\TaskStream\Repositories\TaskStreamRoomRepository;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;


class TaskStreamService extends TaskStreamValidationService
{
    public function __construct(
        protected readonly TaskStreamInvitationRepository $taskStreamInvitationRepository,
        protected readonly UserRepository $userRepository,
        TaskStreamRepository $taskStreamRepository,
        TaskStreamRoomRepository $taskStreamRoomRepository,
    )
    {
        parent::__construct($taskStreamRepository, $taskStreamRoomRepository);
    }

    public function index(): LengthAwarePaginator
    {
        return $this->taskStreamRepository->get();
    }

    /**
     * @throws CValidationException
     */
    public function store()
    {
        $liveRoom = $this->validateAuthLiveRoom();

        $taskStream = $this->taskStreamRepository->createTask($liveRoom->id);

        $this->sendTaskToStream('newTaskStream', $taskStream->id, $liveRoom);

        return $taskStream->load('rooms');
    }

    /**
     * @throws CValidationException
     */
    public function getIntoTask($taskStream)
    {
        $this->validateTaskLiveRoom($taskStream->room_id);

        $liveRoom = $this->validateAuthLiveRoom();

        $this->validateLimit($taskStream);

        $this->validateRoomInAnotherTask($taskStream->id, $liveRoom->id);

        $this->remoteUpdate($taskStream->room_id, $liveRoom->id, 1);

        if ($taskStream->rooms()->count() === 0) {
            $this->taskStreamRepository->createTaskRoom($taskStream, $taskStream->room_id);
        }

        $this->taskStreamRepository->createTaskRoom($taskStream, $liveRoom->id);

        $this->sendTaskToStream('newJoinedTaskStream', $taskStream->id, $liveRoom);

        return $taskStream->load('rooms');
    }

    /**
     * @throws CValidationException
     */
    public function join($data)
    {
        $taskStream = $this->taskStreamRepository->findOrFail($data['task_stream_id']);

        return $this->getIntoTask($taskStream);
    }

    /**
     * @throws CValidationException
     */
    public function leave($data)
    {
        $liveRoom = $this->validateAuthLiveRoom();

        $taskStream = $this->taskStreamRepository->findOrFail($data['task_stream_id']);

        $taskStreamRoom = $this->validateRoomInTask($taskStream, $liveRoom->id);

        if ($taskStream->rooms()->count() === 2) {
            $roomIds = $taskStream->rooms()->pluck('room_id')->toArray();
            $pk = PkSession::where(['status' => 1, 'task_stream_id' => $taskStreamRoom->task_stream_id])->latest()->first();
            if ($pk){
                app(PkSessionService::class)->closeLogic($pk->id, $taskStreamRoom->task_stream_id);
            }

            $this->taskStreamRepository->updateAllRemotes($roomIds);

            $taskStream->rooms()->delete();
        } else {
            $this->remoteUpdate($taskStream->room_id, $liveRoom->id, 0);
            $pk = PkSession::where(['status' => 1, 'task_stream_id' => $taskStreamRoom->task_stream_id])->latest()->first();

            if ($pk) {
                $team1Rooms = explode(',', $pk->team_1);
                $team2Rooms = explode(',', $pk->team_2);

                $roomId = $liveRoom->id;

                if (in_array($roomId, $team1Rooms)) {
                    $team1Rooms = array_diff($team1Rooms, [$roomId]);
                }

                if (in_array($roomId, $team2Rooms)) {
                    $team2Rooms = array_diff($team2Rooms, [$roomId]);
                }

                $pk->update([
                    'team_1' => implode(',', $team1Rooms),
                    'team_2' => implode(',', $team2Rooms),
                ]);

                if (empty($team1Rooms) || empty($team2Rooms)) {
                    app(PkSessionService::class)->closeLogic($pk->id, $taskStreamRoom->task_stream_id);
                }
            }

            $taskStreamRoom->delete();
        }

        return $taskStream->load('rooms');
    }

    /**
     * @throws CValidationException
     */
    public function sendInvitation(array $data)
    {
        $authUser = auth()->user();
        $liveRoom = $this->validateAuthLiveRoom();
        $inviteeUserId = $data['invitee_user_id'];

        $taskStream = $this->taskStreamRepository->findOrFail($data['task_stream_id']);

        if ($inviteeUserId == $authUser->id) {
            throw new CValidationException(__('You cannot invite yourself.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($taskStream->room_id != $liveRoom->id) {
            $this->validateRoomInTask($taskStream, $liveRoom->id);
        }

        if ($taskStream->room_id == $liveRoom->id) {
            $roomsCount = $taskStream->rooms()->count();

            $ownerRoomExists = $this->taskStreamRepository->getExistenceTask($taskStream, $taskStream->room_id);

            if (! $ownerRoomExists && $roomsCount > 0) {
                throw new CValidationException(__('You cannot send an invitation while your task is empty. Please join your task first.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $invitee = User::findOrFail($inviteeUserId);

        $inviteeLiveRoom = $this->validateAuthLiveRoom($invitee);

        if ($inviteeLiveRoom) {
            $isInviteeInTask = $this->taskStreamRepository->getExistenceTask($taskStream, $inviteeLiveRoom->id);

            if ($isInviteeInTask) {
                throw new CValidationException(__('This user is already part of your task stream.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->validateRoomInAnotherTask($taskStream->id, $inviteeLiveRoom->id);
        }

        $existing = $this->taskStreamInvitationRepository->findPendingInvitation($taskStream->id, $inviteeUserId);

        if ($existing) {
            throw new CValidationException(__('This user already has a pending invitation.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->taskStreamInvitationRepository->createInvitation($taskStream->id, $authUser->id, $inviteeUserId);

        $invitationData = ['user_id' => $authUser->id, 'user_name' => $authUser->name ?? '', 'user_image' => $authUser->profile->avatar, 'room_id' => $liveRoom->id, 'task_stream_id' => $taskStream->id, 'battle_data' => @$data['battle_data']];

        event(new TaskStreamInvitation($inviteeUserId, $invitationData));

        return [
            'room_id' => $inviteeLiveRoom->id,
            'user_name' => $invitee->name,
            'user_image' => $invitee->profile->avatar,
        ];
    }

    /**
     * @throws CValidationException
     */
    public function respondInvitation(array $data)
    {
        $authUser = auth()->user();
        $status = $data['status'];
        $taskStream = $this->taskStreamRepository->findOrFail($data['task_stream_id']);

//      if ($this->taskStreamInvitationRepository->hasRecentAcceptedInvitation($taskStream->id)) {
//            throw new CValidationException(__('This task invitation has just been accepted by another user.'), ResponseAlias::HTTP_CONFLICT);
//        }
        $invitation = $this->taskStreamInvitationRepository->findPendingInvitation($taskStream->id, $authUser->id);

        if (! $invitation) {
            throw new CValidationException(__('No pending invitation found for this task.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($status === 'accept') {
            $result = $this->getIntoTask($taskStream);

            $invitation->update(['status' => 'accepted']);

            $liveRoom = $this->validateAuthLiveRoom();
            $invitationResponseData = ['user_id' => $authUser->id, 'user_name' => $authUser->name ?? '', 'room_id' => $liveRoom->id, 'task_stream_id' => $taskStream->id];
            event(new TaskStreamRespondInvitation($invitation->inviter_user_id, $invitationResponseData));

            return $result;
        }

        $invitation->update(['status' => 'rejected']);

        return $taskStream->load('rooms');
    }

    public function liveFriends(): LengthAwarePaginator
    {
        $tasks = $this->taskStreamRoomRepository->getArrayTasks();

        return $this->userRepository->friends($tasks);
    }
}
