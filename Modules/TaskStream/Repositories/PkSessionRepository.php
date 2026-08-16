<?php

namespace Modules\TaskStream\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Carbon\Carbon;
use Modules\TaskStream\Entities\PkSession;

class PkSessionRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new PkSession());
    }

    public function activePkSession($id, $taskStreamId)
    {
        return $this->model->where(['id' => $id, 'status' => 1, 'task_stream_id' => $taskStreamId])->latest()->firstOrFail();
    }

    public function firstOrCreate($taskStreamId, $mergedData)
    {
        $existing = $this->model
            ->where('task_stream_id', $taskStreamId)
            ->where('status', 1)
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->model->create($mergedData);
    }
}
