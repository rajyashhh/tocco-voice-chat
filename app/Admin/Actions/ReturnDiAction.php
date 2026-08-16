<?php

namespace App\Admin\Actions;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\AgencyMangerPullingOut;
use App\Models\AgencySallary;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\SalaryTrx;
use App\Models\User;
use App\Models\UserSallary;
use Encore\Admin\Actions\Action;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReturnDiAction extends Action
{
    public $id;

    protected $selector = '.salary_action';

    public function __construct($id = 0)
    {
        $this->id = $id;
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $coinlog = CoinLog::where('id', request('coinlog'))->first();
        $user = User::where('id', $coinlog->user_id)->first();
        if(!$user){
            return $this->response()->error('User Not Found')->refresh();
        }
        $user->di = @$user->di - $coinlog->obtained_coins;
        $coinlog->obtained_coins = 0;
        $coinlog->delete();
        $user->update();
        return $this->response()->success('success')->refresh();
    }

    public function form()
    {

        $this->hidden('coinlog', __('coinlog'))->value($this->id);
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-danger salary_action ">' . __('Return') . '</a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }
}
