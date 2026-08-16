<?php

namespace Modules\RankingReward\Console;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Models\GiftLog;
use App\Helpers\UserCommon;
use App\Models\CoinGameUser;
use App\Enums\UserCoinLogType;
use Modules\Vip\Entities\OVip;
use Illuminate\Console\Command;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use Modules\RankingReward\Entities\RankingType;
use Modules\RankingReward\Entities\WinnerRanking;
use Modules\Achievement\Entities\UserAchievementLevel;
use App\Jobs\SendFirebaseNotificationIndividualUserJob;
use Illuminate\Support\Facades\Log;

class WeeklyRankingCommand extends Command
{
    protected $signature = 'weekly-ranking';

    protected $description = 'Command description';

    public function handle()
    {
        $rankingTypes = RankingType::where('schedule', 'weekly')->get();


        foreach ($rankingTypes as $rankingType) {

            // 1) Get the ranking list dynamically
            $rankingList = $this->getRankingList($rankingType);

            if (!$rankingList || $rankingList->isEmpty()) {
                //    Log:: warning('Ranking list is empty', [
                //         'ranking_type_id' => $rankingType->id,
                //     ]);
                continue;
            }

            // 2) Apply ranges to give rewards
            $this->applyRanges($rankingList, $rankingType);
        }
    }


    public function getRankingList(RankingType $rankingType)
    {

        switch ($rankingType->type) {

            case 'wealth':
            case 'charm':
                return $this->giftRanking($rankingType->type);

            case 'charge':
                return $this->charge();

            case 'game':
                return $this->gameRanking();

            default:
                return collect();
        }
    }

    public function giftRanking($type)
    {
        $timezone = getTimezone();

        $start = Carbon::now($timezone)->subWeek()->startOfWeek();

        $end = Carbon::now($timezone)->subWeek()->endOfWeek();

        $map = [
            'wealth' => 'sender_id',
            'charm'  => 'receiver_id',
        ];

        $column = $map[$type];
        $withRelation = $type === 'wealth' ? 'sender' : 'receiver';

        return GiftLog::query()
            ->selectRaw("$column, SUM(giftNum * giftPrice) AS total")
            ->with($withRelation)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy($column)
            ->orderByDesc('total')
            ->get()
            ->values();
    }


    public function charge()
    {
        $timezone = getTimezone();
        $start = Carbon::now($timezone)
            ->subWeek()
            ->startOfWeek();

        $end = Carbon::now($timezone)
            ->subWeek()
            ->endOfWeek();
        //
        //  dd($start,$end);

        $query = User::query()
            // Join charges of this week
            ->leftJoinSub(
                fn($q) => $q->select('user_id', DB::raw('SUM(amount) AS total_charge'))
                    ->from('charges')
                    ->where('user_type', 'user')
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('user_id'),
                'charges',
                'users.id',
                'charges.user_id'
            )
            // Join coin_logs of this week
            ->leftJoinSub(
                fn($q) => $q->select('user_id', DB::raw('SUM(obtained_coins) AS total_restore'))
                    ->from('coin_logs')
                    ->where('status', 1)
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('user_id'),
                'coin_logs',
                'users.id',
                'coin_logs.user_id'
            )
            // Select sum and filter only users with activity
            ->select([
                'users.*',
                DB::raw('IFNULL(total_charge,0) + IFNULL(total_restore,0) AS total_sum')
            ])
            ->havingRaw('total_sum > 0') // only users with charge or coins
            ->orderByDesc('total_sum');

        $results = $query->get()->values();
        //dd($results);
        // Log the results count
        // ]);

        return $results;
    }



    public function gameRanking()
    {
        $timezone = getTimezone();
        $start = Carbon::now($timezone)->subWeek()->startOfWeek();

        $end = Carbon::now($timezone)->subWeek()->endOfWeek();

        return CoinGameUser::query()
            ->select('user_id', DB::raw("SUM(CASE WHEN type = 1 THEN coins ELSE 0 END) AS exp"))
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('user')
            ->groupBy('user_id')
            ->orderByDesc('exp')
            ->get()->values();
    }

    protected function getUserIdKey(string $type): string
    {
        return match ($type) {
            'wealth' => 'sender_id',
            'charm'  => 'receiver_id',
            'charge' => 'id',
            'game'   => 'user_id',
            default  => 'id',
        };
    }

    public function applyRanges($rankingList, RankingType $rankingType)
    {
        foreach ($rankingType->ranges as $range) {

            $min = $range->min;
            $max = $range->max ?? $min;

            $startIndex = $min - 1;
            $count = $max - $min + 1;

            $records = $rankingList->slice($startIndex, $count)->values();
            //   dd($records );
            $userIdKey = $this->getUserIdKey($rankingType->type);
            $userIds = $records->pluck($userIdKey)->filter()->values();
            foreach ($records as $record) {


                $this->giveReward($record, $range, $rankingType->type);
            }

            // dd($userIds);
            $this->dispatchNotification($userIds->toArray(), $range);
        }
    }


    protected function dispatchNotification($userIds, $range)
    {
        // Convert to array if it's a Collection
        $userIds = is_array($userIds) ? $userIds : $userIds->toArray();

        if (empty($userIds)) {
            Log::warning('No user IDs found for notification');
            return;
        }

        $min = $range->min;
        $max = $range->max ?? $min;

        $tokens = User::whereIn('id', $userIds)
            ->whereNotNull('notification_id')
            ->pluck('notification_id')
            ->toArray();

        $image = $range->generate_image;
        $icon  = getImagePath($image);
        $data['image'] = $icon;

        if (!empty($tokens)) {
            // Log that the job is being dispatched

            SendFirebaseNotificationIndividualUserJob::dispatch(
                tokens: $tokens,
                data: $data,
                min: $min,
                max: $max,
                dataType: $image
            )->onQueue('notification_heavy');
        } else {
            Log::warning('No tokens found to send notification', [
                'user_ids' => $userIds
            ]);
        }
    }
    public function giveReward($record, $range, $type)
    {
        $type = $type === 'wealth' ? 'sender' : ($type === 'charm' ? 'receiver' : $type);
        $userId =  $record[$type . '_id'] ?? $record['user_id'] ?? $record['id'];
        $timezone = getTimezone();

        // Previous or current week depending on your logic
        $startOfWeek = Carbon::now($timezone)->startOfWeek();
        $endOfWeek   = Carbon::now($timezone)->endOfWeek();
        // dd($record, $range, $type, $userId);
        if (!$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        foreach ($range->rewards as $reward) {

            $exists = WinnerRanking::where([
                'winner_id' => $user->id,
                'reward_id' => $reward->id,
                'type'      => $type,
            ])
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->exists();

            if ($exists) {
                continue;  // reward already given this week
            }
            // Coins
            if ($reward->target_type == "coins") {
                $amountBefore = $user->di;

                UserCoinLogHelper::logByType(
                    $user->id,
                    $reward->target,
                    $amountBefore,
                    UserCoinLogType::GIFT_RANKING
                );

                $user->increment('di', $reward->target);
            }

            // VIP
            elseif ($reward->target_type == "vip") {
                $vip = OVip::find($reward->target);
                UserCommon::addVipToUser($user, $vip, $reward->expire_days, null, 'gift-ranking', sendNotification: 0);
            }

            // Ware
            elseif ($reward->target_type == "ware") {
                $ware = Ware::find($reward->target);
                UserCommon::addWareToUser($user, $ware, $reward->expire_days, null, 'gift-ranking', sendNotification: 0);
            }

            // Achievement
            elseif ($reward->target_type == "achievement") {
                UserAchievementLevel::create([
                    "user_id"     => $user->id,
                    // "custom_image" => $reward->target,
                    'custom_achievement_id' => $reward->target,
                    "end_at"      => now()->addDays($reward->expire_days),
                    'receive_type' => 'gift-ranking',
                ]);
            }

            // Badge
            elseif ($reward->target_type == "badge") {
                Common::userBadge($user->id, $reward->target, $reward->expire_days, 'gift-ranking');
            }

            // Save history
            DB::table('winner_rankings')->insert([
                'winner_id' => $user->id,
                'reward_id' => $reward->id,
                'type'      => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
