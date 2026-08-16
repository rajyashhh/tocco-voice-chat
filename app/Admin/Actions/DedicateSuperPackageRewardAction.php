<?php

namespace App\Admin\Actions;


use Illuminate\Http\Request;

use Encore\Admin\Actions\Action;
use App\Models\SuperPackageReward;
use Modules\Country\Entities\SuperAdmin;
use Modules\Country\Entities\SuperAdminReward;

class DedicateSuperPackageRewardAction extends Action
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

            if (!$superPackage) {
                return $this->response()->error(__('Super Package not found.'));
            }

            $superAdmins = $request->input('super_admin_id', []);

            foreach ($superAdmins as $superAdmin) {
                foreach ($superPackage->packageRewards as $reward) {
                    SuperAdminReward::create([
                        'super_admin_id' => $superAdmin,
                        'type' => $reward->type,
                        'target' => $reward->target,
                        'expire' => $reward->expire,
                        'no_reward' => $reward->quantity,
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
        $this->multipleSelect('super_admin_id', __('Select Super Admins'))
        ->ajax('/admin/search/super-admin', 'id', 'name');
            // ->options(function () {
            //     return SuperAdmin::pluck('name', 'id');
            // });
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
}
