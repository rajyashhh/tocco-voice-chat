<?php

namespace App\Admin\Actions;


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
use App\Models\SuperPackageReward;
use Modules\Country\Entities\SuperAdmin;
use Modules\Region\Entities\AreaManager;
use Modules\Country\Entities\SuperAdminReward;
use Modules\Achievement\Entities\UserAchievementLevel;
use Illuminate\Support\Facades\DB;


class DedicateAdminPackageReward extends Action
{
    public $name;
    protected $selector = '.salary_action';
    public $id;

    public function __construct($id = 0,)
    {
        $this->name = __('dedicate');
        $this->id = $id;

        parent::__construct();
    }

    public function handle(Request $request)
    {
        try {
            $superPackage = SuperPackageReward::with('packageRewards')->find($request->uid);
            $userType = $request->input('user_type');
            if (!$superPackage) {
                return $this->response()->error(__('Super Package not found.'));
            }
            if ($request->input('user_type') == 'user') {
                $user = User::query()->searchByUuid($request->user_uuid)->first();
                if (!$user)   return $this->response()->error(__('dashboard.userNotFound'))->refresh();
                $id = DB::table('admin_rewards')->insertGetId([
                    'super_admin_id' => $user->id,
                    'type' => 'package',
                    'target' => $superPackage->id,
                    'expire' => 1,
                    'no_reward' => 1,
                    'user_type' => $userType,
                    'created_by' => Admin::user()->id,
                    'created_at' => now(),
                ]);
                foreach ($superPackage->packageRewards as $reward) {
                    $this->assignRewards($reward, $user, $id);
                }
                return $this->response()->success(__('Dedicated successfully'))->refresh();
            }

            $superAdmins = $request->input('super_admin_id', []);
            $areaAdmins = $request->input('area_admin_id', []);

            if ($request->input('user_type') == 'region') {
                $superAdmins = $areaAdmins;
            }

            foreach ($superAdmins as $superAdmin) {

                $id = DB::table('admin_rewards')->insertGetId([
                    'super_admin_id' => $superAdmin,
                    'type' => 'package',
                    'target' => $superPackage->id,
                    'expire' =>  1,
                    'no_reward' => 1,
                    'user_type' => $userType,
                    'created_by' => Admin::user()->id,
                    'created_at' => now(),
                ]);
                foreach ($superPackage->packageRewards as $reward) {
                    DB::table('admin_rewards')->insert([
                        'super_admin_id' => $superAdmin,
                        'type' => $reward->type,
                        'target' => $reward->target,
                        'expire' => $reward->expire,
                        'no_reward' => $reward->type == 'coin'  || $reward->type == 'achievement' ? 1 : $reward->quantity,
                        'user_type' => $userType,
                        'created_by' => Admin::user()->id,
                        'package_id' =>  $id,
                        'created_at' => now(),
                    ]);
                }
            }


            return $this->response()->success(__('Dedicated successfully'))->refresh();
        } catch (\Exception $exception) {
            return $this->response()->error(__('Something went wrong') . $exception->getMessage());
        }
    }

    public function form()
    {
        $this->hidden('uid', __('id'))->attribute('id', 'uid');
        $this->select('user_type', __('user Type'))->options(['region' => __('Region Manager'), 'country' => __('Country Manager'), 'user' => __('User')])->default('region')->required()->attribute(['id' => 'user-type-select']);

        $this->multipleSelect('area_admin_id', __('Select Region Manager'))
            ->options(self::getAreaAdmins())->attribute(['id' => 'area-admin-select']);

        $this->multipleSelect('super_admin_id', __('Select Country Manager'))
            ->options(self::getSuperAdmins())->attribute(['id' => 'super-admin-select']);
        $this->text('user_uuid', __('user uuid'))->attribute(['id' => 'user-select']);

        Admin::script(<<<'SCRIPT'
            function toggleUserTypeFields() {
                var selected = $('#user-type-select').val();

                if (selected === 'region') {
                    $('#area-admin-select').closest('.form-group').show();
                    $('#super-admin-select').closest('.form-group').hide();
                     $('#user-select').closest('.form-group').hide();
                } else if (selected === 'country') {
                    $('#super-admin-select').closest('.form-group').show();
                    $('#area-admin-select').closest('.form-group').hide();
                     $('#user-select').closest('.form-group').hide();
                } else if (selected === 'user') {
                    $('#user-select').closest('.form-group').show();
                    $('#super-admin-select').closest('.form-group').hide();
                    $('#area-admin-select').closest('.form-group').hide();
                }
            }

            // listen to correct select
            $(document).on('change', '#user-type-select', toggleUserTypeFields);

            // run on page load
            toggleUserTypeFields();
            SCRIPT);
    }

    public function html()
    {
        return '<a href="#" onclick="dedicateSet(\'' . $this->id . '\')" class="btn btn-sm btn-success salary_action">'
            . __('dedicate') . '</a>
        <script>
            function dedicateSet(uid) {
                $("#uid").val(uid);
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


    protected function assignRewards($request, $user, $packageId)
    {

        $reward = SuperAdminReward::create([
            'super_admin_id' => $user->id,
            'type' => $request->type,
            'target' => $request->target,
            'expire' => $request->expire,
            'no_reward' => 1,
            'user_type' => 'user',
            'created_by' => Admin::user()->id,
            'package_id' => $packageId,
            'created_at' => now(),

        ]);

        switch ($reward->type) {
            case "coin":

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
                if ($ware) UserCommon::addWareToUser($user, $ware, $reward->expire, null, 'admin_dedicate');
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
                    'receive_type' => 'package_dedicate',
                ];
                UserAchievementLevel::create($attributes);
                break;
        }
    }
}
