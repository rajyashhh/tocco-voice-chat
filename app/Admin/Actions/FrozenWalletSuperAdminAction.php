<?php
namespace App\Admin\Actions;

use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FrozenWalletSuperAdminAction extends RowAction
{
    public function name()
    {
        // الاسم ديناميكي بحسب الحالة الحالية
        return $this->row->is_frozen_wallet
            ? __('Disable frozen wallet')
            : __('Enable frozen wallet');
    }

    public function handle(Model $model)
    {
        $model->is_frozen_wallet = !$model->is_frozen_wallet;
        $model->save();
    
        $message = $model->is_frozen_wallet
            ? __('Enabled frozen wallet!')
            : __('Disabled frozen wallet!');
    
        return $this->response()->success($message)->refresh(); // refresh كامل للصف أو للـ Grid
    }
    public function icon()
    {
        return $this->row->is_frozen_wallet ? 'fa-toggle-on' : 'fa-toggle-off';
    }

    public function dialog()
    {
        $msg = $this->row->is_frozen_wallet
            ? __('dashboard.confirm_disable_frozen_wallet')
            : __('dashboard.confirm_enable_frozen_wallet');

        $this->confirm($msg);
    }
}
