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
use Encore\Admin\Facades\Admin;
use Encore\Admin\Actions\Action;
use App\Helpers\UserCoinLogHelper;
use Modules\Country\Entities\SuperAdmin;
use Modules\Region\Entities\AreaManager;
use Modules\Country\Entities\SuperAdminReward;
use Modules\Achievement\Entities\UserAchievementLevel;

class DedicateSuperAdminRewardAction extends Action
{
    public $name;
    protected $selector = '.salary_action';
    public $id;
    public $type;

    public function __construct($id = 0, $type = '')
    {
        $this->name = __('dedicate');
        $this->id = $id;
        $this->type = $type;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        try {

            if ($request->input('user_type') == 'user') {
                $user = User::query()->searchByUuid($request->user_uuid)->first();
                if (!$user)   return $this->response()->error(__('dashboard.userNotFound'))->refresh();

                $this->assignRewards($request, $user);
                return $this->response()->success(__('Dedicated successfully'))->refresh();
            }

            $superAdmins = $request->input('super_admin_id', []);
            $areaAdmins = $request->input('area_admin_id', []);
            $userType = $request->input('user_type');
            if ($request->input('user_type') == 'region') {
                $superAdmins = $areaAdmins;
            }

            $expire = $request->input('expire');
            $noReward = $request->input('no_reward');

            foreach ($superAdmins as $superAdmin) {
                SuperAdminReward::create([
                    'super_admin_id' => $superAdmin,
                    'type' => $request->type,
                    'target' => $request->uid,
                    'expire' => $expire,
                    'no_reward' => $noReward,
                    'user_type' => $userType,
                    'created_by' => Admin::user()->id,

                ]);
            }

            return $this->response()->success(__('Dedicated successfully'))->refresh();
        } catch (\Exception $exception) {
            return $this->response()->error(__('Something went wrong') . $exception->getMessage());
        }
    }

    public function form()
    {
        $this->hidden('uid', __('id'))->attribute('id', 'uid');
        $this->hidden('type', __('id'))->attribute('id', 'type');
        $this->select('user_type', __('user Type'))->options(['region' => __('Region Manager'), 'country' => __('Country Manager'), 'user' => __('User')])->default('region')->required()->attribute(['id' => 'user-type-select']);

        $this->multipleSelect('area_admin_id', __('Select Region Manager'))
            ->options(self::getAreaAdmins())->attribute(['id' => 'area-admin-select']);

        $this->multipleSelect('super_admin_id', __('Select Country Manager'))
            ->options(self::getSuperAdmins())->attribute(['id' => 'super-admin-select']);
        $this->text('user_uuid', __('user uuid'))->attribute(['id' => 'user-select']);

        $this->integer('expire', __('Days'))->default(1);
        $this->integer('no_reward', __('No reward'))->default(1)->attribute(['id' => 'no_reward']);

        Admin::script(<<<'SCRIPT'
                function toggleUserTypeFields() {
                    var selected = $('#user-type-select').val();

                    if (selected === 'region') {
                        $('#area-admin-select').closest('.form-group').show();
                        $('#super-admin-select').closest('.form-group').hide();
                        $('#user-select').closest('.form-group').hide();
                        $('#no_reward').closest('.form-group').show();

                    } else if (selected === 'country') {
                        $('#super-admin-select').closest('.form-group').show();
                        $('#area-admin-select').closest('.form-group').hide();
                        $('#user-select').closest('.form-group').hide();
                        $('#no_reward').closest('.form-group').show();

                    } else if (selected === 'user') {
                        $('#user-select').closest('.form-group').show();
                        $('#super-admin-select').closest('.form-group').hide();
                        $('#area-admin-select').closest('.form-group').hide();
                        $('#no_reward').closest('.form-group').hide();
                    }
                }

                // listen to select change
                $(document).on('change', '#user-type-select', toggleUserTypeFields);

                // run on page load
                toggleUserTypeFields();
                SCRIPT);
    }

    public function html()
    {
        return '<a href="#" onclick="dedicateSet(\'' . $this->id . '\', \'' . $this->type . '\')" class="btn btn-sm btn-success salary_action">'
            . __('dedicate') . '</a>
        <script>
            function dedicateSet(uid, type) {
                $("#uid").val(uid);
                $("#type").val(type);
            }
        </script>';
    }



    protected static function getSuperAdmins()
    {
        static $admins = null;

        if ($admins === null) {
            $admins = SuperAdmin::query()
                ->where('type', 'country')
                ->whereNull('deleted_at')
                ->pluck('name', 'id')
                ->toArray();
        }

        return $admins;
    }


    protected static function getAreaAdmins()
    {
        static $admins = null;

        if ($admins === null) {
            $admins = AreaManager::query()
                ->where('type', 'region')
                ->whereNull('deleted_at')
                ->pluck('name', 'id')
                ->toArray();
        }

        return $admins;
    }


    protected function assignRewards($request, $user)
    {
        $expire = $request->input('expire');
        $reward = SuperAdminReward::create([
            'super_admin_id' => $user->id,
            'type' => $request->type,
            'target' => $request->uid,
            'expire' => $expire,
            'no_reward' => 1,
            'user_type' => 'user',
            'created_by' => Admin::user()->id,

        ]);

        switch ($reward->type) {
            case "coins":

                $amountBefore = $user->di;
                UserCoinLogHelper::logByType(
                    $user->id,
                    $reward->target,
                    $amountBefore,
                    UserCoinLogType::ADMIN_REWARD,
                );

                $user->di += $reward->target;
                $user->save();


                break;
            case "vip":
                $vip = OVip::find($reward->target);
                UserCommon::addVipToUser($user, $vip, $reward->expire, null, 'admin_dedicate');
                break;
            case "ware":
                $ware = Ware::find($reward->target);
                UserCommon::addWareToUser($user, $ware, $reward->expire, null, 'admin_dedicate');
                break;
            case "badge":
                Common::userBadge($user->id, $reward->target, $reward->expire, 'admin_dedicate');
                break;
            case "achievement":
                $dateTimestamp = Carbon::parse($reward->expire)->format("Y-m-d H:i:s");
                $attributes = [
                    'user_id'       => $user->id,
                    'custom_achievement_id' => $reward->target,
                    'end_at' => $dateTimestamp,
                    'receive_type' => 'admin_dedicate',
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
