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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalariesAction extends Action
{
    public $name;
    public $options = [];
    public $id;
    public $type;

    protected $selector = '.salary_action';

    public function __construct($id = 0, $type = 'user')
    {
        $this->id = $id;
        $this->type = $type;
        $this->name = __('cashing');
        $this->options = ['agency' => __('agency'), 'agency_users' => __('agency users')];
        parent::__construct();
    }

    public function handle(Request $request)
    {
        $amount = \request('amount');


        try {
            DB::beginTransaction();
            $type = \request('type') ?? 'user';
            if (\request('id') && $type == 'agency') {
                $agency = Agency::query()->find(\request('id'));
                if ($agency) {
                    if ($request->select_type == 'decrement') {
                        if ($agency->salary < $amount) {
                            return $this->response()->error(__('low balance'))->refresh();
                        }
                    }

                    $m = $amount ?: $agency->salary;
                    $userSallary = AgencySallary::where('agency_id', \request('id'))->latest('created_at')->first();

                    if (!$userSallary) {
                        $userSallary = new AgencySallary();
                        $userSallary->agency_id=\request('id');
                        $userSallary->sallary=0;
                        if ($request->select_type == 'increment') {
                            $userSallary->cut_amount = -$m;
                        }
                        if ($request->select_type == 'decrement') {
                            $userSallary->cut_amount = $m;
                        }
                        $userSallary->month = now()->month;
                        $userSallary->year = now()->year;
                        $userSallary->is_paid = 0;
                        $userSallary->save();
                    }else{
                        if ($request->select_type == 'increment') {
                            $userSallary->cut_amount += -$m;
                            $userSallary->update();
                        }
                        if ($request->select_type == 'decrement') {
                            $userSallary->cut_amount += $m;
                            $userSallary->update();
                        }
                    }

                    if ($request->select_type == 'decrement') {
                        $m = -$m;
                    }
                    if ($m > 0) {
                        SalaryTrx::query()->create(
                            [
                                'type' => 1,
                                'oid' => $agency->id,
                                'amount' => $m,
                                't_no' => rand(11111111, 99999999),
                                'note' => 'paid via admin',
                                'payer_id' => auth()->id(),
                                'payer_type' => 0
                            ]
                        );
                    }
                }
            }
            elseif (\request('id') && $type == 'user') {
                $user = User::query()->find(\request('id'));
                if ($user) {
                    if ($request->select_type == 'decrement') {
                        if ($user->salary < $amount) {
                            return $this->response()->error(__('low balance'))->refresh();
                        }
                    }
                    $m = $amount ?: $user->salary;
                    if ($m > 0) {
                        $userSallary = UserSallary::where('user_id', \request('id'))->latest('created_at')->first();
                        if ($userSallary == null) {
                            $userSallary= new UserSallary();
                            $userSallary->user_id=\request('id');
                            $userSallary->hours="0 / 0";
                            $userSallary->days="0 / 0";
                            $userSallary->sallary=0;
                            $userSallary->agency_sallary=0;
                            if ($request->select_type == 'increment') {
                                $userSallary->cut_amount = -$m;
                            }
                            if ($request->select_type == 'decrement') {
                                $userSallary->cut_amount = $m;
                            }
                            $userSallary->month = now()->month;
                            $userSallary->year = now()->year;
                            $userSallary->is_paid = 0;
                            $userSallary->save();
                        }else{
                            if ($request->select_type == 'increment') {
                                $userSallary->cut_amount -= $m;
                                $userSallary->save();
                            }
                            if ($request->select_type == 'decrement') {
                                $userSallary->cut_amount += $m;
                                $userSallary->save();
                            }
                        }

                         if ($request->select_type == 'decrement') {
                             $amount = -$amount;
                         }
                        SalaryTrx::query()->create(
                            [
                                'type' => 0,
                                'oid' => $user->id,
                                'amount' => ($amount ?: $user->salary),
                                't_no' => rand(11111111, 99999999),
                                'note' => 'paid via admin',
                                'payer_id' => auth()->id(),
                                'payer_type' => 0,
                                'transaction_type'=>$request->select_type

                            ]
                        );
                    }
                }
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
        $this->hidden('id', __('id'))->attribute('id', 'salary-user-id');
        if ($this->type == 'user') {
            $this->hidden('type', 'type')->value('user');
        } else {
            $this->hidden('type', __('type'))->value('agency');
        }
        $this->text('amount', __('amount'))->help(__('let it empty to pay total'));
        $this->select('select type',__('admin.selectType'))->options(['increment' => __('admin.increment'), 'decrement' => __('admin.decrement')]);
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="putSalary(' . $this->id . ')" class="btn btn-sm btn-success salary_action ">'.__('admin.edit').'</a>
<script>
function putSalary(val) {
  $("#salary-user-id").val(val)
}
</script>
';
    }
}
