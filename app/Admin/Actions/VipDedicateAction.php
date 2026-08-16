<?php

namespace App\Admin\Actions;

use Carbon\Carbon;
use Modules\Vip\Entities\OVip;
use App\Models\User;
use App\Helpers\Common;
use Modules\Vip\Entities\UserVip;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Encore\Admin\Facades\Admin;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Modules\Vip\Helpers\VipCommon;

class VipDedicateAction extends Action
{
    protected $selector = '.salary_action';
    public $id;

    public function __construct($id = null)
    {
        $this->id = $id;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        try {
            $countryID = Common::filterCountryIds();


            // Validate user
            $user = User::query()
                ->when($countryID, fn($q) => $q->whereIn('country_id', $countryID))
                ->searchByUuid($request->user_uuid)->first();

            if (!$user) {
                return $this->response()->error(__('dashboard.userNotFound'))->refresh();
            }

            // Get VIP
            $vip = OVip::find($request->get('id'));
            if (!$vip) {
                return $this->response()->error('VIP not found')->refresh();
            }

            // Check admin permissions
            if (!Admin::user()->can('*') && $request->days > 30) {
                return $this->response()->error(__('dashboard.addAchivement'))->refresh();
            }

            DB::beginTransaction();

            $enableVipAuto = config('admin.isUsed_vip');


            VipCommon::createUserVip($vip, $user, $request->days, Admin::user()->id, '', 1, 0, 0, 'admin-dedicate');


            DB::commit();

            CustomNotification::vips($user, $request->days, $vip->img);

            $title = 'VIP Assigned';
            $body = 'You have received VIP access for :days days from admin.';

            CustomNotification::charges($user, $title, $body, ['days' => $request->days]);

            return $this->response()->success(__('dashboard.successful'));
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->response()->error(__('dashboard.error'))->refresh();
        }
    }

    public function form()
    {
        $this->hidden('id')->default($this->id);
        $this->integer('days', __('days'))->rules(['required', 'integer', 'min:1']);
        $this->text('user_uuid', __('user uuid'))->required();
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-info salary_action">' . __('dedicate') . '</a>
    <script>
    function pu(val) {
        $("input[name=\'id\']").val(val);
    }
    </script>';
    }
}
