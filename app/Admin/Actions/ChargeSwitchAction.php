<?php
namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ChargeSwitchAction extends RowAction
{
    public function name()
    {
        // الاسم ديناميكي بحسب الحالة الحالية
        return !$this->row->transfer_salary
            ? __('Disable Transfer Salary')
            : __('Enable Transfer Salary');
    }

    public function handle(Model $model)
    {
        $model->transfer_salary = !$model->transfer_salary;
        $model->save();
    
        $message = !$model->transfer_salary
            ? __('Enabled Transfer Salary!')
            : __('Disabled Transfer Salary!');
    
        return $this->response()->success($message)->refresh(); // refresh كامل للصف أو للـ Grid
    }
    public function icon()
    {
        return !$this->row->transfer_salary ? 'fa-toggle-on' : 'fa-toggle-off';
    }

    public function dialog()
    {
        $msg = !$this->row->transfer_salary
            ? __('dashboard.confirm_disable_transfer_salary')
            : __('dashboard.confirm_enable_transfer_salary');

        $this->confirm($msg);
    }
}
