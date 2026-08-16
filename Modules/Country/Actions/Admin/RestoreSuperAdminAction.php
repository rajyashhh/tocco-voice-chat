<?php

namespace Modules\Country\Actions\Admin;

use App\Models\Bd;
use App\Models\User;
use Modules\Country\Entities\SuperAdmin;
use Illuminate\Http\Request;
use Modules\Country\Entities\SuperAdminReward;
use Encore\Admin\Actions\Action;

class RestoreSuperAdminAction extends Action
{
    public $name;
    protected $selector = '.salary_action';
    public $id;

    public function __construct($id = 0)
    {
        $this->name = __('restore super admin');
        $this->id = $id;

        parent::__construct();
    }

    public function handle(Request $request)
    {
        try {
            $superAdmin = SuperAdmin::withTrashed()->find($request->uid);
            $anotherSuPerAdmin = SuperAdmin::where('country_id', $superAdmin->country_id)->first();
            if ($anotherSuPerAdmin) return $this->response()->error(__('can not restore this super admin'))->refresh();
            $user = User::find($superAdmin->app_id);
            if ($user->is_super_admin) return $this->response()->error(__('can not restore this super admin user taken'))->refresh();
            $superAdmin->restore();
            $default = SuperAdmin::where('default', 1)->first();
            Bd::where('country_id', $superAdmin->country_id)->where('parent_id', $default->id)->update(['parent_id' => $superAdmin->id]);
            return $this->response()->success(__(' successfully'))->refresh();
        } catch (\Exception $exception) {
            return $this->response()->error(__('Something went wrong') . $exception->getMessage());
        }
    }

    public function form()
    {
        $this->hidden('uid', __('id'))->attribute('id', 'uid');
    }

    public function html()
    {
        return '<a href="#" onclick="dedicateSet(\'' . $this->id . '\')" class="btn btn-sm btn-success salary_action">
                <i class="fa fa-refresh"></i>
            </a>
        <script>
            function dedicateSet(uid) {
                $("#uid").val(uid);
            }
        </script>';
    }

    public function getHandleRoute()
    {
        return url(request()->segment(1) . '/_handle_action_');
    }
}
