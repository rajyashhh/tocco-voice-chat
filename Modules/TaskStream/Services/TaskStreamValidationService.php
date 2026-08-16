<?php

namespace Modules\TaskStream\Services;

use App\Exceptions\CValidationException;
use App\Helpers\Common;
use App\Models\Room;
use Modules\TaskStream\Repositories\TaskStreamRepository;
use Modules\TaskStream\Repositories\TaskStreamRoomRepository;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TaskStreamValidationService
{
    public function __construct(
        protected readonly TaskStreamRepository $taskStreamRepository,
        protected readonly TaskStreamRoomRepository $taskStreamRoomRepository,
    )
    {
    }

    /**
     * @throws CValidationException
     */
    protected function validateTaskLiveRoom($taskRoomId)
    {
        $liveRoom = Room::where(['id' => $taskRoomId, 'type' => 'live', 'is_live' => 1])->first();

        if (! $liveRoom){
            throw new CValidationException(__('This room is not live in current time'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($liveRoom->uid === auth()->id()) {
            $taskStream = $this->taskStreamRepository->findByRoomId($liveRoom->id);

            if ($taskStream && $taskStream->rooms()->count() === 0) {
                throw new CValidationException(__('You cannot join your own task stream while there is nobody.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return $liveRoom;
    }

    /**
     * @throws CValidationException
     */
    protected function validateAuthLiveRoom($checkUser = null)
    {
        $user = $checkUser ?: auth()->user();
        if (! $user) {
            throw new CValidationException(__('You dont have live room or not live'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
        $liveRoom = $user->ownerRoom()->where('type', 'live')->where('is_live', 1)->first();

        if (! $liveRoom){
            throw new CValidationException(__('You dont have live room or not live'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $liveRoom;
    }

    /**
     * @throws CValidationException
     */
    protected function validateRoomInTask($taskStream, $liveRoomId)
    {
        $taskStreamRoom = $this->taskStreamRepository->getExistenceTask($taskStream, $liveRoomId);

        if (!$taskStreamRoom) {
            throw new CValidationException(__('Your room is not part of this task stream.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $taskStreamRoom;
    }

    /**
     * @throws CValidationException
     */
    protected function validateRoomInAnotherTask($taskStreamId, $liveRoomId): void
    {
        $alreadyInTask = $this->taskStreamRoomRepository->checkExistenceTask($taskStreamId, $liveRoomId);

        if ($alreadyInTask) {
            throw new CValidationException(__('This room is already part of another task stream.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * @throws CValidationException
     */
    protected function validateLimit($taskStream): void
    {
        if ($taskStream->rooms()->count() >= 4) {
            throw new CValidationException(__('This task stream has reached the maximum number of rooms allowed.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    protected function remoteUpdate($taskStreamRoomId, $liveRoomId, $status): void
    {
        if ($taskStreamRoomId != $liveRoomId) {
            $myTask = $this->taskStreamRepository->findByRoomId($liveRoomId);

            if ($myTask) {
                $myTask->update(['is_remote' => $status]);
            }
        }
    }

    protected function sendTaskToStream(string $message, $taskStreamId, $liveRoom): void
    {
        $data = [
            "messageContent" => [
                "message" => $message,
                'task_stream' => $taskStreamId,
                'room_id' => $liveRoom->id
            ]
        ];
        $json = json_encode($data);

        Common::sendToStream('SendCustomCommand', $liveRoom->id, $liveRoom->uid, $json);
    }

    /**
     * @throws CValidationException
     */
    public function checkRoomsIds($allRoomIds, $liveRoomId): void
    {
        $existing = Room::whereIn('id', $allRoomIds)->where('type','live')->where('is_live',1)->pluck('id')->toArray();
        $missing = array_diff($allRoomIds, $existing);
        if (! empty($missing)) throw new CValidationException(__('Some rooms are not live or do not exist: ') . implode(',', $missing), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        if (! in_array($liveRoomId, $allRoomIds)) throw new CValidationException(__('Host must be part of the teams'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
    }
}
