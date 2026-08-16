<?php

namespace App\Http\Controllers\Dashboard\Events;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\Events\AdminEventReportResource;
use App\Models\Pack;
use Modules\Vip\Entities\UserVip;
use Illuminate\Http\Request;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Events\Entities\GeneralRole;
use Modules\Events\Entities\RewardWinnerPk;
use Modules\Events\Entities\UserChargeEvent;
use Modules\Events\Entities\WinnerReward;
use Modules\Events\Http\Actions\EventReportAction;
use Modules\Events\Services\LoseWinnerRewards;

class AdminGeneralRolesController extends Controller
{
    public function show(string $id)
    {
        $data = GeneralRole::where('type',$id)->first();
        if(!$data){
            return response()->json([
                'meesage' => 'roles not found'
            ],423);
        }
        return $data;
    }

    public function update(Request $request, string $id)
    {
        if((int)$request->item_exists === 1)
        {
            $data = GeneralRole::where('type',$id)->first();
        }
        else{
            $data =new  GeneralRole();
        }
        $request->validate([
            'desc_en' => 'required|string',
            'desc_ar' => 'required|string',
            'url' => 'required|string',
        ]);
        $data->desc_en = $request->desc_en;
        $data->desc_ar = $request->desc_ar;
        $data->url     = $request->url;
        $data->type    = $id;
        $data->save();
        return 200;
    }

    public function reports($type)
    {
        if($type === 'pk')
        {
            $data = RewardWinnerPk::with('winner','reward')->get();
        }
        else if($type === 'weekly_star')
        {
            $data = WinnerReward::where('type','weekly_star')->with('winner','reward')->get();
        }
        else if($type === 'event_period')
        {
            $data = WinnerReward::where('type','event_period')->with('winner','reward')->get();
        }
        else if($type === 'charge_benefit')
        {
            $data = UserChargeEvent::with('winner','event')->get();
        }
        return AdminEventReportResource::collection( $data);
    }

    public function delete_reports($id,$type)
    {
        $type = $type;
        $winner_reward_id= $id;

        if($type === 'pk')
        {
            $winner_reward = RewardWinnerPk::query()->find($winner_reward_id);
        }
        else if($type === 'weekly_star')
        {
            $winner_reward=WinnerReward::query()->find($winner_reward_id);
        }
        else if($type === 'event_period')
        {
            $winner_reward=WinnerReward::query()->find($winner_reward_id);
        }

        $winner= $winner_reward?->winner;
        if ($winner_reward && $winner){
            if ($winner_reward->reward->type == 'coins'){
                $target=$winner_reward->reward->target;
                $winner_reward->winner->di -= $target;
                $winner_reward->winner->save();
            }
            elseif ($winner_reward->reward->type == 'vip'){
                $userVip = UserVip::query()->where(["user_id" => $winner->id, "vip_id" => $winner_reward->reward->target])->latest()->first();
                $userVip->delete();
                /*if ($userVip){

                    Pack::query()->where(['user_id'=>$winner->id,"target_id"=>$winner_reward->reward->target])->delete();
                }*/
                (new LoseWinnerRewards())->removePacksVip($winner_reward,$userVip, $winner,$winner_reward->reward->expire);
            }
            elseif ($winner_reward->reward->type == 'ware'){
                Pack::query()->where(['user_id'=>$winner->id,"target_id"=>$winner_reward->reward->target])->delete();
            }
            elseif ($winner_reward->reward->type == "achievement"){
                $attributes = [
                    'user_id'       => $winner->sender_id,
                    'custom_image' => $winner_reward->reward->target,
                ];

                UserAchievementLevel::query()->where($attributes)->delete();
            }
            $winner_reward->delete();
        }
        return  200;
    }


}
