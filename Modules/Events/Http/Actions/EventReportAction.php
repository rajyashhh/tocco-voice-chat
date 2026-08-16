<?php

namespace Modules\Events\Http\Actions;

use App\Models\Pack;
use Modules\Vip\Entities\UserVip;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Modules\Achievement\Entities\UserAchievementLevel;
use Modules\Events\Entities\RewardWinnerPk;
use Modules\Events\Entities\WinnerReward;
use Modules\Events\Services\LoseWinnerRewards;

class EventReportAction extends Action
{
    public $name;
    public $options = [];
    public $id ;
    public $type;

    protected $selector = '.salary_action';

    public function __construct ($id=0,$type=null)
    {
        $this->id = $id;
        $this->type=$type;
        parent::__construct ();
    }

    public function handle(Request $request)
    {
        $type = $request->input('type');
        $winner_reward_id=\request('id');
        if ($type == "weekly"){
            $winner_reward=WinnerReward::query()->find($winner_reward_id);
        }elseif ($type == "pk"){
            $winner_reward = RewardWinnerPk::query()->find($winner_reward_id);
        }
        $winner=$winner_reward?->winner;
        if ($winner_reward && $winner){
            if ($winner_reward->reward->type == 'coins'){
                $target=$winner_reward->reward->target;
                $winner_reward->winner->di -= $target;
                $winner_reward->winner->save();
            }elseif ($winner_reward->reward->type == 'vip'){
                $userVip = UserVip::query()->where(["user_id" => $winner->id, "vip_id" => $winner_reward->reward->target])->latest()->first();
                $userVip->delete();
                /*if ($userVip){

                    Pack::query()->where(['user_id'=>$winner->id,"target_id"=>$winner_reward->reward->target])->delete();
                }*/
                (new LoseWinnerRewards())->removePacksVip($winner_reward,$userVip, $winner,$winner_reward->reward->expire);
            }elseif ($winner_reward->reward->type == 'ware'){
                Pack::query()->where(['user_id'=>$winner->id,"target_id"=>$winner_reward->reward->target])->delete();
            }elseif ($winner_reward->reward->type == "achievement"){
                $attributes = [
                    'user_id'       => $winner->sender_id,
                    'custom_image' => $winner_reward->reward->target,
                ];

                UserAchievementLevel::query()->where($attributes)->delete();
            }
            $winner_reward->delete();
        }
        return $this->response()->success('success')->refresh();
    }


    public function form()
    {
        $this->hidden('id', __('id'))->attribute('id', 'vid');
        $this->hidden('type')->default($this->type);
    }

    public function html()
    {
        $return = __('return');
        return '<a href="javascript:void(0);" onclick="pu('.$this->id.')" class="btn btn-sm btn-danger salary_action ">' .$return. '</a>
        <script>
        function pu(val) {
          $("#vid").val(val)
           $("#type").val(type);
        }
        </script>
        ';
    }

}
