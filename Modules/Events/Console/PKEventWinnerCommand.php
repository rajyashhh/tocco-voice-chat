<?php

namespace Modules\Events\Console;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use Carbon\Carbon;
use Modules\Vip\Entities\OVip;
use App\Models\Ware;
use App\Models\GiftLog;
use App\Helpers\UserCommon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Events\Entities\PkEvent;
use Modules\Events\Entities\PkWinner;
use Modules\Achievement\Entities\UserAchievementLevel;

class PKEventWinnerCommand extends Command
{
    protected $signature = 'pk-event-winner';

    protected $description = 'Command description';

    public function handle()
    {
        $pkEvent = $this->getCurrentPkEvent();
        if (!$pkEvent) {
            return '';
        }

        $this->processEventParticipants($pkEvent, 'sender', 'pk-king');
        $this->processEventParticipants($pkEvent, 'receiver', 'pk-star');
        $this->processEventParticipants($pkEvent, 'roomowner', 'pk-room');

        //        $this->info(now()->toDateTimeString() . ' '. $this->signature . ' Run successful...');
    }

    protected function getCurrentPkEvent()
    {
        return PkEvent::endToday()->with('rewards')
            ->first();
    }

    protected function processEventParticipants(PkEvent $pkEvent, $participantType, $pkType)
    {
        $participants = $this->getEventParticipants($pkEvent, $participantType);
        foreach ($participants as $index => $participant) {
            $userId = $participant->{$participantType . '_id'};
            $user = $participant->{$participantType === 'roomowner' ? 'roomOwner' : $participantType};

            if (!$user) {
                continue;
            }

            if ($this->isAlreadyWinner($pkEvent->id, $userId, $pkType)) {
                continue;
            }

            $level = $index + 1;
            $rewards = $pkEvent->rewards->where('level', $level)->where('pk_type', $pkType);

            try {
                DB::transaction(function () use ($pkEvent, $userId, $level, $pkType, $rewards, $user) {
                    $winner = $this->createWinner($pkEvent->id, $userId, $level, $pkType);
                    $this->assignRewards($winner, $rewards, $user);
                });
            } catch (QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                    continue;
                }
                throw $e;
            }

            try {
                \App\Facades\CustomNotification::pkEventWinner($user, $level, $pkType);
            } catch (\Throwable $e) {
                Log::error('pk-event-winner notification failed', [
                    'user_id' => $user->id,
                    'pk_type' => $pkType,
                    'level' => $level,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function getEventParticipants($pkEvent, $participantType)
    {
        $column = $participantType === 'roomowner' ? 'roomowner_id' : $participantType . '_id';
        $relation = $participantType === 'roomowner' ? 'roomOwner' : $participantType;

        return GiftLog::whereBetween('created_at', [$pkEvent->start_date, $pkEvent->end_date])
            ->with([$relation])
            ->where('pk', 1)
            ->when($participantType === 'roomowner', fn ($q) => $q->where('roomowner_id', '!=', 0))
            ->select(DB::raw('SUM(giftPrice) AS total_gift_num'), $column)
            ->groupBy($column)
            ->orderByDesc('total_gift_num')
            ->take(3)
            ->get();
    }

    protected function isAlreadyWinner($pkEventId, $userId, $pkType)
    {
        return PkWinner::where([
            'pk_event_id' => $pkEventId,
            'user_id' => $userId,
            'pk_type' => $pkType,
        ])->exists();
    }

    protected function createWinner($pkEventId, $userId, $level, $pkType)
    {
        return PkWinner::create([
            'pk_event_id' => $pkEventId,
            'user_id' => $userId,
            'level' => $level,
            'pk_type' => $pkType,
        ]);
    }

    protected function assignRewards($winner, $rewards, $user)
    {
        foreach ($rewards as $reward) {
            DB::table('reward_winner_pks')->insert([
                'pk_winner_id' => $winner->id,
                'pk_reward_id' => $reward->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            switch ($reward->type) {
                case "coins":
                    $amountBefore = $user->di;
                    $user->increment('di', $reward->target);

                    UserCoinLogHelper::logByType(
                        $user->id,
                        $reward->target,
                        $amountBefore,
                        UserCoinLogType::PK,
                    );
                    break;
                case "vip":
                    $vip = OVip::find($reward->target);
                    if ($vip) {
                        UserCommon::addVipToUser($user, $vip, $reward->expire, null, 'pk-event');
                    }
                    break;
                case "ware":
                    $ware = Ware::find($reward->target);
                    if ($ware) {
                        UserCommon::addWareToUser($user, $ware, $reward->expire, null, 'pk-event');
                    }
                    break;
                case "badge":
                    Common::userBadge($user->id, $reward->target, $reward->expire, 'pk-event');
                    break;
                case "achievement":
                    $dateTimestamp = Carbon::parse($reward->expire)->format("Y-m-d H:i:s");
                    $attributes = [
                        'user_id'       => $user->id,
                        'custom_achievement_id' => $reward->target,
                        'end_at' => $dateTimestamp,
                        'receive_type' => 'pk-event',
                    ];
                    UserAchievementLevel::create($attributes);
                    break;
            }
        }
    }
}
