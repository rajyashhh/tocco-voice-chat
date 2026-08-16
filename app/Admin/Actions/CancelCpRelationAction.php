<?php

namespace App\Admin\Actions;

use App\Models\Charge;
use App\Helpers\Common;
use App\Models\Setting;
use Modules\CP\Entities\Cp;
use Illuminate\Http\Request;
use App\Models\ChargeInvoice;
use App\Models\ShippingAgency;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Actions\Action;
use Encore\Admin\Admin as Script;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CancelCpRelationAction extends Action
{
    public $name;
    protected $selector = '.charge_action';
    protected $cpId;

    public function setCpId($cpId): static
    {
        $this->cpId = $cpId;
        return $this;
    }

    public function handle(Request $request)
    {
        
        $Cp = Cp::find($request->cp_id);
        $Cp->status = 3;
        $Cp->save();


        return $this->response()->success('canceled Successfully')->refresh();
    }





    public function form()
    {
        $this->hidden('cp_id')->attribute(['id' => 'vid']);
        $this->confirm(__('messages.confirm_delete'), __('messages.cancelCp'), [
            'icon' => 'warning',
            'showCancelButton' => true,
            'confirmButtonText' => __('messages.yes_delete'),
            'cancelButtonText' => __('messages.cancel'),
        ]);    
    }


    function html()
    {
        $title = __('cancel cp');
        $html = '';

        //if (Admin::user()->can('add-switch-' .'coin-recharge') || Admin::user()->can('*')) {
        $html .= '<a href="javascript:void(0);" onclick="pu(' . $this->cpId . ')" class="charge_action btn btn-sm text-white" style="background-color: #28a745; border-color: #28a745; color: white;">'
            . htmlspecialchars($title) .
            '</a>';
        // }



        $html .= <<<HTML
<script>
function pu(val) {
    $("#vid").val(val);
}
</script>
HTML;

        return $html;
    }
}
