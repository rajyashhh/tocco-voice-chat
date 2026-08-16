<?php

namespace Modules\TribeReward\Jobs;

use App\Helpers\Common;
use App\Models\Agency;
use App\Models\GiftLog;
use Carbon\Carbon;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\TribeReward\Entities\TribePeriod;
use Modules\TribeReward\Entities\TribeReward;
use Modules\TribeReward\Entities\TribeTop;

class AgencyTribeRewardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::beginTransaction();
        try {
            $period = TribePeriod::where('end_date', '<', now())
                ->orderBy('end_date', 'desc')
                ->first();

            if (!$period) {
                DB::rollBack();
//                info('No ended period found.');
                return;
            }

            $agencyRanks = GiftLog::selectRaw('agency_id, SUM(giftPrice) as total_exp')
                ->whereBetween('created_at', [$period->start_date, $period->end_date])
                ->whereNotNull('agency_id')
                ->whereHas('agency')
                ->where('agency_id', '!=', 0)
                ->groupBy('agency_id')
                ->orderByDesc('total_exp')
                ->get();

            $tribesTops = TribeTop::where('tribe_period_id', $period->id)->get();

            foreach ($tribesTops as $top) {
                $rewards = TribeReward::where('tribe_top_id', $top->id)->get();

                $eligibleAgencies = $agencyRanks->slice($top->min - 1, $top->max - $top->min + 1);

                foreach ($eligibleAgencies as $agencyRank) {

                    $agency = Agency::find($agencyRank->agency_id);

                    if ($agency){
                        $agencyRewardData = array_map(function ($reward) use ($agency){
                            return [
                                'agency_id' => $agency->id,
                                'type' => $reward->type,
                                'target_type' => $reward->target_type,
                                'target' => $reward->target,
                                'quantity' => $reward->quantity,
                                'available_quantity' => $reward->quantity,
                                'expire_days'       => $reward->expire_days,
                                'expire_at' => Carbon::now()->addDays($reward->expire_days ?? 15),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }, $rewards->all());

                        DB::table('agency_rewards')->insert($agencyRewardData);
                    }

                    if ($agency && $agency->owner) {
                        Common::sendOfficialMessage(
                            $agency->owner->id,
                            __('Congratulations!'),
                            __('Your agency has received rewards from the event!')
                        );

                        $notificationToken = \Illuminate\Support\Facades\DB::table('users')->where('id', $agency->owner->id)->value('notification_id');
                        $title = app()->getLocale() == 'ar'
                            ? config('app.name_ar')
                            : config('app.name_en');
                        $body = __('Your agency has received rewards from the event!') . " " . $agency->owner->name;
                        Common::send_firebase_notification([$notificationToken], $title, $body);
                    }
                }
            }

            $futurePeriod = TribePeriod::where('start_date', '>', now())->first();

            if (!$futurePeriod) {
                $newStart = $period->end_date;
                $newEnd = Carbon::parse($period->end_date)->addDays(15);
                $newPeriod = TribePeriod::create([
                    'start_date' => $newStart,
                    'end_date' => $newEnd,
                ]);

                foreach ($tribesTops as $oldTop) {
                    $newTop = $oldTop->replicate();
                    $newTop->tribe_period_id = $newPeriod->id;
                    $newTop->save();

                    $oldRewards = TribeReward::where('tribe_top_id', $oldTop->id)->get();
                    foreach ($oldRewards as $oldReward) {
                        $newReward = $oldReward->replicate();
                        $newReward->tribe_top_id = $newTop->id;
                        $newReward->save();
                    }
                }
            }

            DB::commit();
//            info('Agency event rewards distributed and new period created if necessary.');
        } catch (\Throwable $e) {
            DB::rollBack();
//            \Log::error("ERROR in AgencyTribeRewardJob Job: " . $e->getMessage());
            throw $e;
        }
    }
}
