<?php

namespace App\Admin\Actions;

use App\Models\Agency;
use App\Models\AgencySallary;
use App\Models\SalaryTrx;
use App\Models\UsdTransfer;
use App\Models\User;
use App\Models\UserSallary;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaySalariesAction extends Action
{
    public $name;
    public $options = [];
    public $id;
    public $type;
  //  public $salary;

    protected $selector = '.salary_pay_action';

    public function __construct($id = 0, $type = 'user',)
    {
        $this->id = $id;
        $this->type = $type;
       // $this->salary = $salary;
        $this->name = __('cashing');
        $this->options = ['agency' => __('agency'), 'agency_users' => __('agency users')];
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $amount = \request('amount');
        try {
            DB::beginTransaction();
            if ( $request->type == "user" ) {
                $user = User::findOrFail(\request('id')); 
                $amount = $amount ?? $user->salary;
                if ($user->salary < $amount) {
                    return $this->response()->error(__('low balance'))->refresh();
                }

                UserSallary::updateOrCreate(
                    [
                        'user_id' => $user->id ,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'cut_amount' => DB::raw("cut_amount + $amount"),
                        // 'pending_dollar' => DB::raw("pending_dollar - $amount")
                    ]
                );
                SalaryTrx::query()->create(
                    [
                        'type' => 0,
                        'oid' => $user->id,
                        'amount' => -($amount ?: $user->salary),
                        't_no' => rand(11111111, 99999999),
                        'note' => 'paid via admin',
                        'payer_id' => auth()->id(),
                        'payer_type' => 0,
                        'transaction_type'=> 'decrement'
                    ]
                );

            }elseif ( $request->type == "agency") {
                $agency = Agency::findOrFail(\request('id'));
                $amount = $amount ?? $agency->salary;

                if ($agency->salary < $amount) {
                    return $this->response()->error(__('low balance'))->refresh();
                }

                AgencySallary::updateOrCreate(
                    [
                        'agency_id' => $agency->id ,
                        'month' => date('m'),
                        'year' => date('Y')
                    ],
                    [
                        'cut_amount' => DB::raw("cut_amount + $amount"),
                    ]
                );
                SalaryTrx::query()->create(
                    [
                        'type' => 1,
                        'oid' => $agency->id,
                        'amount' => -$amount,
                        't_no' => rand(11111111, 99999999),
                        'note' => 'paid via admin',
                        'payer_id' => auth()->id(),
                        'payer_type' => 0
                    ]
                );
            }





            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->response()->error($exception->getMessage())->refresh();
        }

        return $this->response()->success('success')->refresh();
    }

    public function form()
    {
        $this->hidden('id', __('id'))->attribute('id', 'newvid');
       // $this->hidden('salary', __('salary'))->value($this->salary);
        if ($this->type == 'user') {
            $this->hidden('type', 'type')->value('user');
        } else {
            $this->hidden('type', __('type'))->value('agency');
        }
        $this->text('amount', __('amount'))->help(__('let it empty to pay total'));
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-primary salary_pay_action ">'.__('admin.pay').'</a>
            <script>
            function pu(val) {
                $("#newvid").val(val)
            }
            </script>
            ';
    }
}
