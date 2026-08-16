<?php

namespace Modules\TribeReward\Services;

use App\Helpers\UserCommon;
use App\Models\GiftLog;
use App\Models\User;
use App\Models\Ware;
use Carbon\Carbon;
use Exception;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\TribeReward\Entities\AgencyReward;
use Modules\TribeReward\Entities\TribePeriod;
use Modules\Vip\Entities\OVip;

class TribeService
{
    public function index()
    {
        return TribePeriod::with('tribeTops.tribeRewards')->where('end_date', '<', now())
            ->orderBy('end_date', 'desc')
            ->first();
    }

    public function agencyRanking()
    {
        $perPage = request('per_page', 10);
        $tribePeriod = TribePeriod::where('end_date', '<', now())->orderBy('end_date', 'desc')->first();

        if (! $tribePeriod) return false;

        return GiftLog::whereHas('agency')
            ->selectRaw('agency_id, SUM(giftPrice) as total_exp')
            ->whereBetween('created_at', [$tribePeriod->start_date, $tribePeriod->end_date])
            ->whereNotNull('agency_id')
            ->where('agency_id', '!=', 0)
            ->groupBy('agency_id')
            ->orderByDesc('total_exp')
            ->paginate($perPage);
    }

    /**
     * @throws Exception
     */
    public function agencyRewards()
    {
        $perPage = request('per_page', 10);

        $user = auth()->user();
        $agency = $user->ownAgency;

        if (!$agency) {
            throw new Exception(__('you dont have permission'), 403);
        }

        return AgencyReward::where('agency_id', $agency->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @throws Exception
     */
    public function sendUserRewards(array $data, $id): bool
    {
        $user = auth()->user();
        $AgencyReward = AgencyReward::findOrFail($id);
        $userOwnAgency = $user->ownAgency;

        if (!$userOwnAgency || $userOwnAgency->id != $AgencyReward->agency_id) {
            throw new Exception(__('you dont have permission'), 403);
        }

        $userIds = $data['user_ids'];

        $eligibleUsers = User::whereIn('id', $userIds)
            ->where('agency_id', $AgencyReward->agency_id)
            ->get();

        if ($eligibleUsers->count() != count($userIds)) {
            throw new Exception(__('One or more users are not eligible or do not belong to this agency.'), 422);
        }

        $total = $eligibleUsers->count() * $data['quantity'];
        if ($AgencyReward->available_quantity < $total) {
            throw new Exception(__('Not enough available quantity for this reward.'), 422);
        }

        if ($AgencyReward->expire_at && now()->gt($AgencyReward->expire_at)) {
            throw new Exception(__('This reward has expired.'), 422);
        }

        if (
            $AgencyReward->expire_at == null &&
            !empty($AgencyReward->expire_days) &&
            now()->gt(Carbon::parse($AgencyReward->created_at)->addDays($AgencyReward->expire_days))
        ) {
            throw new Exception(__('This reward has expired.'), 422);
        }

        foreach ($eligibleUsers as $user) {
            for ($i = 0; $i < $data['quantity']; $i++) {
                switch ($AgencyReward->target_type) {
                    case "vip":
                        $vip = OVip::find($AgencyReward->target);
                        UserCommon::addVipToUser($user, $vip, $AgencyReward->expire_days, $userOwnAgency,'tribe');
                        break;
                    case "ware":
                        $ware = Ware::find($AgencyReward->target);
                        UserCommon::addWareToUser($user, $ware, $AgencyReward->expire_days, $userOwnAgency,'tribe');
                        break;
                    case "achievement":
                        $dateTimestamp = Carbon::parse($AgencyReward->expire_days)->format("Y-m-d H:i:s");
                        $attributes = [
                            'user_id'       => $user->id,
                            'custom_achievement_id' => $AgencyReward->target,
                            'end_at' => $dateTimestamp,
                            'receive_type' => 'tribe',
                        ];
                        UserAchievementLevel::create($attributes);
                        break;
                }
            }
        }

        $AgencyReward->decrement('available_quantity', $total);

        return true;
    }
}
