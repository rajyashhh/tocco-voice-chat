<?php

namespace Modules\Events\Console;

use App\Models\Ware;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Helpers\UserCommon;
use App\Enums\UserCoinLogType;
use Modules\Vip\Entities\OVip;
use Illuminate\Console\Command;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Modules\Events\Entities\Winner;
use Modules\Events\Entities\WeeklyStar;
use Modules\Achievement\Entities\UserAchievementLevel;

class WeeklyStarWinner extends Command
{
    protected $signature = 'weekly-star-winner';

    protected $description = 'Command description';

    public function handle()
    {
        $weeklyEvent = WeeklyStar::endToday()->where('type', 'weekly_star')
            ->with('gifts', 'rewards')
            ->latest()
            ->first();

        if (!$weeklyEvent) {
            return '';
        }

        $giftIds = $weeklyEvent->gifts->pluck('id');
        $leaderboard = GiftLog::whereIn('giftId', $giftIds)
            ->whereBetween('created_at', [$weeklyEvent->start_date, $weeklyEvent->end_date])
            ->with('sender')
            ->select(DB::raw('SUM(giftPrice) AS total_gift_num'), 'sender_id')
            ->groupBy('sender_id')
            ->orderByDesc('total_gift_num')
            ->take(3)
            ->get();

        foreach ($leaderboard as $index => $entry) {
            $level = $index + 1;
            $sender = $entry->sender;

            if (!$sender) {
                Log::warning('weekly-star-winner: sender missing, skipping', [
                    'weekly_star_id' => $weeklyEvent->id,
                    'sender_id' => $entry->sender_id,
                    'level' => $level,
                ]);
                continue;
            }

            $alreadyWinner = Winner::where([
                'weekly_star_id' => $weeklyEvent->id,
                'user_id' => $entry->sender_id,
            ])->exists();

            if ($alreadyWinner) {
                continue;
            }

            try {
                DB::transaction(function () use ($weeklyEvent, $entry, $sender, $level) {
                    $winner = Winner::create([
                        'weekly_star_id' => $weeklyEvent->id,
                        'user_id' => $entry->sender_id,
                        'level' => $level,
                    ]);

                    $rewards = $weeklyEvent->rewards->where('level', $level);
                    foreach ($rewards as $reward) {
                        $expiredAt = now()->addDays((int) $reward->expire);

                        if ($reward->type == 'coins') {
                            $amountBefore = $sender->di;
                            UserCoinLogHelper::logByType(
                                $sender->id,
                                $reward->target,
                                $amountBefore,
                                UserCoinLogType::WEEKLY_STAR,
                            );
                            $sender->increment('di', $reward->target);
                        } elseif ($reward->type == 'vip') {
                            $vip = OVip::query()->find($reward->target);
                            UserCommon::addVipToUser($sender, $vip, $reward->expire, null, 'weekly-star');
                        } elseif ($reward->type == 'ware') {
                            $ware = Ware::query()->find($reward->target);
                            UserCommon::addWareToUser($sender, $ware, $reward->expire, null, 'weekly-star');
                        } elseif ($reward->type == 'achievement') {
                            UserAchievementLevel::create([
                                'user_id' => $entry->sender_id,
                                'custom_achievement_id' => $reward->target,
                                'end_at' => $expiredAt->format('Y-m-d H:i:s'),
                                'receive_type' => 'weekly-star',
                            ]);
                        } elseif ($reward->type == 'badge') {
                            Common::userBadge($entry->sender_id, $reward->target, $reward->expire, 'weekly-star');
                        } else {
                            continue;
                        }

                        DB::table('winner_rewards')->insert([
                            'winner_id' => $winner->id,
                            'reward_id' => $reward->id,
                            'type' => 'weekly_star',
                            'expaired_at' => $expiredAt,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) == 1062) {
                    continue;
                }
                throw $e;
            }

            try {
                \App\Facades\CustomNotification::weeklyStarWinner($sender, $level);
            } catch (\Throwable $e) {
                Log::warning('weekly-star-winner: notification failed', [
                    'user_id' => $sender->id,
                    'level' => $level,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}