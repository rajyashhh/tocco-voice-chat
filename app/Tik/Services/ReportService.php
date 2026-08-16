<?php

namespace App\Tik\Services;

use App\Models\Pack;
use Modules\Vip\Entities\UserVip;
use App\Tik\Repositories\UserRepository;
use Modules\Events\Entities\WinnerReward;
use App\Http\Resources\UserReportResource;
use App\Tik\Repositories\AgencyRepository;
use App\Http\Resources\ReportEventResource;
use Modules\Events\Entities\RewardWinnerPk;
use App\Http\Resources\AgencyReportResource;
use App\Tik\Repositories\AdminUsersRepository;
use Modules\Events\Services\LoseWinnerRewards;
use App\Http\Resources\AdminUserReportResource;
use Modules\Achievement\Entities\UserAchievementLevel;

class ReportService
{
    public function __construct(
        private readonly AgencyRepository $agencyRepository,
        private readonly UserRepository $userRepository,
        private readonly AdminUsersRepository $adminUsersRepository,
    ) {}

    public function report($request)
    {
        if ($request->type == 'users') {
            $data = $this->userRepository->report($request->uuid, $request->agency_id, $request->month, $request->year, $request->per_page, $request->page);
            return UserReportResource::collection($data);
        } elseif ($request->type == 'agencies') {
            $data = $this->agencyRepository->report($request->id, $request->month, $request->year, $request->per_page, $request->page);
            return AgencyReportResource::collection($data);
        } elseif ($request->type == 'agencies_manger') {
            $data = $this->adminUsersRepository->report($request->id, $request->per_page, $request->page);
            return AdminUserReportResource::Collection($data);
        }
    }

    public function eventReports($request)
    {
        $id = $request->id;
        if ($request->type == 'weekly_star' || $request->type == 'event_period') {
            $data = WinnerReward::where('type', $request->type)->when($request->type == 'weekly_star', fn($q) => $q->where('type', 'weekly_star')->orWhere('type', null))->with('winner', 'reward', 'reward.vip', 'reward.ware')->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($request->per_page, ['*'], 'page', $request->page);
            return ReportEventResource::collection($data);
        } elseif ($request->type == 'pk_event') {
            $data = RewardWinnerPk::with('winner', 'reward', 'reward.vip', 'reward.ware')->when(isset($id), function ($query) use ($id) {
                $query->where('id', $id);
            })->paginate($request->per_page, ['*'], 'page', $request->page);
            return ReportEventResource::collection($data);
        }
    }

    public function returnReward($request)
    {
        $type = $request->input('type');
        $winner_reward_id = $request->input('reward_id');
        if ($type == "weekly") {
            $winner_reward = WinnerReward::query()->find($winner_reward_id);
        } elseif ($type == "pk") {
            $winner_reward = RewardWinnerPk::query()->find($winner_reward_id);
        }
        $winner = $winner_reward?->winner;
        if ($winner_reward && $winner) {
            if ($winner_reward->reward->type == 'coins') {
                $target = $winner_reward->reward->target;
                $winner_reward->winner->di -= $target;
                $winner_reward->winner->save();
            } elseif ($winner_reward->reward->type == 'vip') {
                $userVip = UserVip::query()->where(["user_id" => $winner->id, "vip_id" => $winner_reward->reward->target])->latest()->first();
                $userVip->delete();

                (new LoseWinnerRewards())->removePacksVip($winner_reward, $userVip, $winner, $winner_reward->reward->expire);
            } elseif ($winner_reward->reward->type == 'ware') {
                Pack::query()->where(['user_id' => $winner->id, "target_id" => $winner_reward->reward->target])->delete();
            } elseif ($winner_reward->reward->type == "achievement") {
                $attributes = [
                    'user_id'       => $winner->sender_id,
                    'custom_image' => $winner_reward->reward->target,
                ];

                UserAchievementLevel::query()->where($attributes)->delete();
            }
            $winner_reward->delete();
        }
        return true;
    }
}
