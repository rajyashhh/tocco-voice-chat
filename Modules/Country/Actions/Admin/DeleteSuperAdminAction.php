<?php

namespace Modules\Country\Actions\Admin;

use Modules\Country\Entities\SuperAdmin;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DeleteSuperAdminAction extends RowAction
{
        public $name;

        protected $agencyCount = 0;

        public function __construct()
        {
            parent::__construct();
            $this->name = __('delete');
        }

        public function setModel(Model $model)
        {
//            $this->agencyCount = Agency::where('bd_id', $model->app_id)->count();
            return parent::setModel($model);
        }


        public function handle(Model $model, Request $request)
        {
            if ($model->default == 1) {
                return $this->response()->error(__('You cannot delete the default super admin.'))->refresh();
            }
            if ($model->country_id == 0 ) $this->response()->error(__('can not delete default super admin'))->refresh();


            if ($model->created_by == 'owner') {
                return $this->response()->error(__('You cannot delete a super admin created by the owner.'))->refresh();
            }

            if ($this->agencyCount > 0) {
                $defaultBd = SuperAdmin::where('default', 1)->where('id', '!=', $model->app_id)->first();
                if (!$defaultBd) {
                    return $this->response()->error(__('No default super admin found to transfer agencies to.'))->refresh();
                }

//                Agency::where('bd_id', $model->id)->update(['bd_id' => $defaultBd->app_id]);
            }

            $model->delete();

            return $this->response()->success(__('super admin deleted successfully.'))->refresh();
        }


        public function dialog()
        {
            $this->confirm(__('dashboard.chickDelete'),'',[]);
        }

        public function getHandleRoute()
        {
            return url(request()->segment(1) . '/_handle_action_');
        }
}
