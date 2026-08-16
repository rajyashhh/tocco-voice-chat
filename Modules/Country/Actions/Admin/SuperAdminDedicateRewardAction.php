<?php

namespace Modules\Country\Actions\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Ware;
use App\Helpers\Common;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use App\Enums\UserCoinLogType;
use Modules\Vip\Entities\OVip;
use Modules\Country\Entities\SuperAdminReward;
use Encore\Admin\Actions\Action;
use App\Helpers\UserCoinLogHelper;
use Illuminate\Support\Facades\DB;
use Modules\Achievement\Entities\UserAchievementLevel;

class SuperAdminDedicateRewardAction extends Action
{
    public $name;
    protected $selector = '.salary_action';
    public $id;


    public function __construct($id = 0)
    {
        $this->name = __('dedicate');
        $this->id = $id;
        parent::__construct();
    }


    public function handle(Request $request)
    {
        $user = User::query()->searchByUuid($request->user_uuid)->first();
        if (!$user) {
            return $this->response()->error(__('dashboard.userNotFound'))->refresh();
        }

        // if ($user->country_id != auth()->user()->country_id) {
        //     return $this->response()->error(__('Sorry, you can only manage users in your own country.'))->refresh();        }

        $reward = SuperAdminReward::find($request->id);
        $rewardNom =  $reward->no_reward -  $reward->gave_reward_no;
        try {
            if ($rewardNom !== 0) {
                $this->assignRewards($reward, $user);
                $reward->gave_reward_no += 1;
                $reward->save();
                return $this->response()->success(__('dashboard.successful'));
            }
            return $this->response()->error(__('your reward finished'));
        } catch (\Exception $exception) {

            return $this->response()->error('you dedicate all reward');
        }
    }

    public function form()
    {
        $this->hidden('id', __('id'))->attribute('id', 'vid');
        $this->text('user_uuid', __('user uuid'));
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-info salary_action ">' . __('dedicate') . '</a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }


    protected function assignRewards($reward, $user)
    {

        DB::table('dedicate_admin_rewards')->insert([
            'user_id' => $user->id,
            'reward_id' => $reward->id,
            'admin_id' => auth()->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        switch ($reward->type) {
            case "coin":

                $amountBefore = $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $reward->target,
                    $amountBefore,
                    UserCoinLogType::SUPER_ADMIN_REWARD,
                );

                $user->di += $reward->target;
                $user->save();


                break;
            case "vip":
                $vip = OVip::find($reward->target);
                UserCommon::addVipToUser($user, $vip, $reward->expire, null, 'super_admin_dedicate');
                break;
            case "ware":
                $ware = Ware::find($reward->target);
                UserCommon::addWareToUser($user, $ware, $reward->expire, null, 'super_admin_dedicate');
                break;
            case "badge":
                Common::userBadge($user->id, $reward->target, $reward->expire, 'super_admin_dedicate');
                break;
            case "achievement":
                $dateTimestamp = Carbon::parse($reward->expire)->format("Y-m-d H:i:s");
                $attributes = [
                    'user_id'       => $user->id,
                    'custom_achievement_id' => $reward->target,
                    'end_at' => $dateTimestamp,
                    'receive_type' => 'super_admin_dedicate',
                ];
                UserAchievementLevel::create($attributes);
                break;
        }
    }

    public function getHandleRoute()
    {
        return url(request()->segment(1) . '/_handle_action_');
    }
}
