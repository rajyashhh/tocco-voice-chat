<?php

namespace App\Admin\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;

class DeleteShippingAgencyAction extends RowAction
{
    public $name;

    public function __construct($id = 0)
    {
        $this->name = __("dashboard.delete");
        parent::__construct();
    }
    public function handle(Model $model, Request $request)
    {
        try {
            DB::beginTransaction();
            $owner = User::find($model->app_owner_id);

            $model->delete();
            DB::commit();
            return $this->response()->success(__('dashboard.successful'))->refresh();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->response()->error($exception->getMessage())->refresh();
        }
    }

    public function dialog()
    {
      //  $this->confirm(__('dashboard.chickDelete'), '', []);

      $this->confirm(__('dashboard.chickDelete'), __('messages.deleteShipping'), [
            'icon' => 'warning',
            'showCancelButton' => true,
            'confirmButtonText' => __('messages.yes'),
            'cancelButtonText' => __('messages.cancel'),
        ]);
    }
}
