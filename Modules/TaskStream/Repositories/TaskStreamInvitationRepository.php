<?php

namespace Modules\TaskStream\Repositories;

use App\Tik\Repositories\AbstractRepository;
use Carbon\Carbon;
use Modules\TaskStream\Entities\TaskStreamInvitation;

class TaskStreamInvitationRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct(new TaskStreamInvitation());
    }

    public function createInvitation($taskStreamId, $inviterUserId, $inviteeUserId)
    {
        return $this->model->create([
            'task_stream_id' => $taskStreamId,
            'inviter_user_id' => $inviterUserId,
            'invitee_user_id' => $inviteeUserId,
            'status' => 'pending',
        ]);
    }


    public function findPendingInvitation($taskStreamId, $inviteeUserId)
    {
        // $tz = getTimezone();
        $subMinute = Carbon::now()->subMinute();

        return $this->model
            ->where('task_stream_id', $taskStreamId)
            ->where('invitee_user_id', $inviteeUserId)
            ->where('status', 'pending')
            ->where('created_at', '>=', $subMinute)
            ->latest()->first();
    }

    public function hasRecentAcceptedInvitation($taskStreamId): bool
    {
        $tz = getTimezone();
        $recentTime = Carbon::now($tz)->subMinute();
        return $this->model
                    ->where('task_stream_id', $taskStreamId)
                    ->where('status', 'accepted')
                    ->where('updated_at', '>=', $recentTime)
                    ->exists();
    }
}
