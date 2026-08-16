<?php

namespace Modules\TaskStream\Jobs;

use App\Models\GiftLog;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\TaskStream\Entities\PkSession;

class PkSessionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    /**
     * @throws \Throwable
     */
    public function handle(): true
    {
        $tz = getTimezone();
        $now = Carbon::now($tz);

        $sessions = PkSession::where('status', 1)
            ->where('ends_at', '<=', $now)
            ->get();

        if ($sessions->isEmpty()) {
            return true;
        }

        foreach ($sessions as $pk) {
            DB::transaction(function () use ($pk) {
                $team1Rooms = explode(',', $pk->team_1);
                $team2Rooms = explode(',', $pk->team_2);

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
        }

        return true;
    }

    protected function calculateTeamScore(array $roomIds, PkSession $session): float
    {
        return GiftLog::whereIn('room_id', $roomIds)
            ->where('created_at', '>=', $session->created_at)
            ->where('created_at', '<=', $session->ends_at)
            ->sum('giftPrice');
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
