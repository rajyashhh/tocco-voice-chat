<?php

namespace App\Admin\Actions;

use App\Helpers\Common;
use Modules\Vip\Entities\OVip;
use App\Models\Pack;
use App\Models\User;
use Modules\Vip\Entities\UserVip;
use App\Models\Ware;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditPackExpireAction extends RowAction
{
    public $name;
    public function __construct($id = 0)
    {
        $this->name = __("dashboard.free");
        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        $ex = ($request->days?:0);
        $num = $request->use_num?:0;

        if ($request->type == 0){
            $model->expire += $ex * 86400;
            $model->use_num += $num;
        }else{
            $model->expire -= $ex * 86400;
            $model->use_num -= $num;
        }
        $model->save ();
        return $this->response()->success (__('dashboard.successful'))->refresh ();
    }

    public function form()
    {
        $this->radio('type',__ ('type'))->options ([0=>__('dashboard.raise'),1=>__('dashboard.lower')]);
        $this->integer('days', __('days'));
        $this->integer('use_num', __('num'));
    }
}
