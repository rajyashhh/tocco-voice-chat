<?php

namespace App\Admin\Actions;

use App\Events\UserStatus;
use App\Facades\CustomNotification;
use App\Models\Ban;
use App\Models\Bd;
use App\Models\User;
use Encore\Admin\Actions\Action;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Auth\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BdChargeSwitchAction extends RowAction
{
    public function name()
    {
        return !$this->row->transfer_salary
            ? __('Disable Transfer Salary')
            : __('Enable Transfer Salary');
    }

    public function handle(Model $model)
    {
        // عكس القيمة مباشرةً
        Bd::where("id", $model->id)->update([
            'transfer_salary' => DB::raw('NOT transfer_salary')
        ]);

        $model->refresh(); // ✅ تحديث الموديل بعد التغيير

        $message = !$model->transfer_salary
        ? __('enable_transfer_salary_success')
        : __('disable_transfer_salary_success');

        $response = !$model->transfer_salary ? 'success' : 'error';

        // هنا هيعمل Refresh للصفحة تلقائياً ويعرض الرسالة
        return $this->response()->$response($message)->refresh();
    }

    public function icon()
    {
        return !$this->row->transfer_salary
            ? 'fa-toggle-on text-success'
            : 'fa-toggle-off text-danger';
    }

    public function dialog()
    {
        $msg = $this->row->transfer_salary
                ? __('confirm_enable_transfer_salary')
                : __('confirm_disable_transfer_salary');
           

        $this->confirm($msg, '', []);
    }
}