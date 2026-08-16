<?php

namespace Modules\TaskStream\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\TaskStream\Entities\TaskStream;

class TaskStreamRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new TaskStream());
    }

    public function get(): LengthAwarePaginator
    {
        return $this->model->where('is_remote', 0)->with(['rooms'])->paginate(request('per_page', 10));
    }

    public function findOrFail(int $id,array $relations = []): Model|Collection|Builder|array|null
    {
        return $this->model->where('is_remote', 0)->findOrFail($id);
    }

    public function createTask($liveRoomId)
    {
        return $this->model->firstOrCreate(['room_id' => $liveRoomId]);
    }

    public function createTaskRoom($taskStream, $liveRoomId)
    {
        $taskStream->rooms()->firstOrCreate([
            'room_id' => $liveRoomId,
        ]);

        return $taskStream;
    }

    public function getExistenceTask($taskStream, $liveRoomId)
    {
        return $taskStream->rooms()->where('room_id', $liveRoomId)->first();
    }

    public function findByRoomId($roomId)
    {
        return $this->model->where('room_id', $roomId)->first();
    }

    public function updateAllRemotes(array $roomIds)
    {
        return $this->model->whereIn('room_id', $roomIds)->update(['is_remote' => 0]);
    }
}
