<?php

namespace Modules\TaskStream\Services;

use App\Exceptions\CValidationException;
use App\Models\GiftLog;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Modules\TaskStream\Repositories\PkSessionRepository;
use Modules\TaskStream\Repositories\TaskStreamRepository;
use Modules\TaskStream\Repositories\TaskStreamRoomRepository;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PkSessionService extends TaskStreamValidationService
{
    public function __construct(
        private readonly PkSessionRepository $pkSessionRepository,
        TaskStreamRepository $taskStreamRepository,
        TaskStreamRoomRepository $taskStreamRoomRepository,
    )
    {
        parent::__construct($taskStreamRepository, $taskStreamRoomRepository);
    }

    /**
     * @throws CValidationException
     */
    public function start($data)
    {
        $liveRoom = $this->validateAuthLiveRoom();
        $taskRoom = $this->taskStreamRoomRepository->getRoomTask($liveRoom->id);
        if (! $taskRoom){
            throw new CValidationException(__('You are not in any task.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }
        $taskStream = $this->taskStreamRepository->findOrFail($taskRoom->task_stream_id);
        $allRoomIds = array_merge($data['team_1'], $data['team_2']);

        $this->checkRoomsIds($allRoomIds, $liveRoom->id);

        $count = $this->taskStreamRoomRepository->countRoomsInTask($taskStream->id, $allRoomIds);

        if ($count !== count($allRoomIds)) {
            throw new CValidationException(__('One or more rooms do not belong to your task stream.'), ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        $endsAt = Carbon::now()->copy()->addMinutes($data['duration']);

        $mergedData = [
            'team_1' => implode(',', $data['team_1']),
            'team_2' => implode(',', $data['team_2']),
            'task_stream_id' => $taskStream->id,
            'ends_at' => $endsAt,
            'status' => 1,
        ];

        $pk = $this->pkSessionRepository->firstOrCreate($taskStream->id, $mergedData);
        $pk->duration = $data['duration'];
        return $pk;
    }

    /**
     * @throws CValidationException
     * @throws \Throwable
     */
    public function close($data)
    {
        $liveRoom = $this->validateAuthLiveRoom();
        $taskRoom = $this->taskStreamRoomRepository->getRoomTask($liveRoom->id);

        [$pk, $team1Rooms, $team2Rooms] = $this->closeLogic($data['pk_id'], $taskRoom->task_stream_id);

        $participants = array_merge(
            $this->buildParticipantArray($team1Rooms, 1, $pk->winner, $pk),
            $this->buildParticipantArray($team2Rooms, 2, $pk->winner, $pk)
        );

        $pk->participants_data = $participants;
        return $pk;
    }

    /**
     * @throws \Throwable
     */
    public function closeLogic($pkId, $taskStreamId): array
    {
        $pk = $this->pkSessionRepository->activePkSession($pkId, $taskStreamId);

        $team1Rooms = explode(',', $pk->team_1);
        $team2Rooms = explode(',', $pk->team_2);

        DB::transaction(function () use ($pk, $team1Rooms, $team2Rooms) {
            $team1Score = $this->calculateTeamScore($team1Rooms, $pk);
            $team2Score = $this->calculateTeamScore($team2Rooms, $pk);

            $winner = match(true) {
                $team1Score > $team2Score => 1,
                $team2Score > $team1Score => 2,
                default => 0,
            };

            $pk->update([
                'team_1_score' => $team1Score,
                'team_2_score' => $team2Score,
                'winner' => $winner,
                'status' => 0,
            ]);

            $this->updateWinStreaks($winner, $team1Rooms, $team2Rooms);
        });

        return [$pk, $team1Rooms, $team2Rooms];
    }
    protected function calculateTeamScore(array $roomIds, $session): float
    {
        return GiftLog::whereIn('room_id', $roomIds)
            ->where('created_at', '>=', $session->created_at)
            ->where('created_at', '<=', $session->ends_at)
            ->sum('giftPrice');
    }

    protected function buildParticipantArray(array $roomIds, int $team, int $winnerTeam, $pk): array
    {
        $participants = [];

        $scores = GiftLog::whereIn('room_id', $roomIds)
            ->select('room_id', DB::raw('SUM(giftPrice) as total_score'))
            ->where('created_at', '>=', $pk->created_at)
            ->where('created_at', '<=', $pk->ends_at)
            ->groupBy('room_id')
            ->pluck('total_score', 'room_id');

        foreach ($roomIds as $roomId) {
            $participants[] = [
                'room_id' => (int) $roomId,
                'score' => $scores[$roomId],
                'won' => $team == $winnerTeam,
            ];
        }

        return $participants;
    }

    protected function updateWinStreaks(int $winner, array $team1Rooms, array $team2Rooms): void
    {
        $team1Uids = Room::whereIn('id', $team1Rooms)->pluck('uid');
        $team2Uids = Room::whereIn('id', $team2Rooms)->pluck('uid');

        if ($winner === 0) {
            return;
        }

        $winningUids = $winner === 1 ? $team1Uids : $team2Uids;
        $losingUids  = $winner === 1 ? $team2Uids : $team1Uids;

        User::whereIn('id', $winningUids)->increment('win_streak');

        User::whereIn('id', $losingUids)->update(['win_streak' => 0]);
    }
}
