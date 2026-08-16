<?php

namespace App\Admin\Actions;

use App\Helpers\Common;
use App\Models\Admin;
use App\Models\Agency;
use App\Models\AgencyMangerPullingOut;
use App\Models\Charge;
use App\Models\CoinLog;
use App\Models\SalaryTrx;
use App\Models\User;
use Encore\Admin\Actions\Action;
use Encore\Admin\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalaryAction extends Action
{
    public $name;
    public $options = [];
    public $id ;
    public $type;

    protected $selector = '.salary_action';

    public function __construct ($id=0,$type='user')
    {
        $this->id = $id;
        $this->type = $type;
        $this->name = __ ('cashing');
        $this->options = ['agency'=>__('agency'),'agency_users'=>__('agency users'),'agency_manger'=>__('agency manager')];
        parent::__construct ();
    }

    public function handle(Request $request)
    {
        $amount = \request ('amount');
        try {
            DB::beginTransaction ();
            if (\request ('id') && \request ('type') == 'agency'){
                $agency = Agency::query ()->find (\request ('id'));
                if ($agency){
                    if ($agency->salary < $amount){
                        return $this->response()->error(__ ('low balance'))->refresh();
                    }
                    $ta = $agency->target($request->month);
                    if ($amount){
                        $ta->increment ('cut_amount',$amount);
                    }else{
                        $ta->is_paid = 1;
                        $ta->save ();
                    }

                    $m = $amount?:$agency->salary ;
                    if ($m > 0){
                        SalaryTrx::query ()->create (
                            [
                                'type'=>1,
                                'oid'=>$agency->id,
                                'amount'=>$m,
                                't_no'=>rand (11111111,99999999),
                                'note'=>'paid via admin',
                                'payer_id'=>auth ()->id (),
                                'payer_type'=>0
                            ]
                        );
                    }
                }
            }
            elseif (\request ('id') && \request ('type') == 'user'){
                $user = User::query ()->find (\request ('id'));
                if ($user){
                    if ($user->salary < $amount){
                        return $this->response()->error(__ ('low balance'))->refresh();
                    }
                    $m = $amount?:$user->salary ;
                    $ta = $user->target($request->month);
                    if ($amount){
                        $ta->increment ('cut_amount',$amount);
                    }else{
                        $ta->is_paid = 1;
                        $ta->save ();
                    }
                    if ($m > 0){
                        SalaryTrx::query ()->create (
                            [
                                'type'=>0,
                                'oid'=>$user->id,
                                'amount'=>$amount?:$user->dalary,
                                't_no'=>rand (11111111,99999999),
                                'note'=>'paid via admin',
                                'payer_id'=>auth ()->id (),
                                'payer_type'=>0
                            ]
                        );
                    }
                }
            }
            elseif (\request ('id') && \request ('type') == 'agency_users'){
                $agency = Agency::query ()->find (\request ('id'));
                if ($agency){
                    if ($agency->salary < $amount){
                        return $this->response()->error(__ ('low balance'))->refresh();
                    }
                    $ta = $agency->target($request->month);
                    if ($amount){
                        $ta->increment ('cut_amount',$amount);
                    }else{
                        $ta->is_paid = 1;
                        $ta->save ();
                    }

                    $m = $amount?:$agency->salary ;
                    if ($m > 0){
                        SalaryTrx::query ()->create (
                            [
                                'type'=>0,
                                'oid'=>$agency->id,
                                'amount'=>$agency->old_usd,
                                't_no'=>rand (11111111,99999999),
                                'note'=>'paid via admin',
                                'payer_id'=>auth ()->id (),
                                'payer_type'=>0
                            ]
                        );
                    }
                    $users = $agency->users;
                    foreach ($users as $user){
                        if ($user->salary < $amount){
                            continue;
                        }
                        $m = $amount?:$user->salary ;
                        $ta = $user->target($request->month);
                        if ($amount){
                            $ta->increment ('cut_amount',$amount);
                        }else{
                            $ta->is_paid = 1;
                            $ta->save ();
                        }
                        if ($m > 0){
                            SalaryTrx::query ()->create (
                                [
                                    'type'=>0,
                                    'oid'=>$user->id,
                                    'amount'=>$user->old_usd,
                                    't_no'=>rand (11111111,99999999),
                                    'note'=>'paid via admin for agency , contact your agent for your salary',
                                    'payer_id'=>auth ()->id (),
                                    'payer_type'=>0
                                ]
                            );
                        }
                    }
                }
            } elseif (\request ('id') && \request ('type') == 'agency_manger'){


                         $user_id=\request ('id');
                    $m = Common::AgencyMangerCash($user_id);

                    if ($m > $amount){
                        AgencyMangerPullingOut::query ()->create (
                            [
                                'agency_manger_id'=>$user_id,
                                'amount'=>$amount,
                            ]
                        );
                    }else{
                        return $this->response()->error( __('this salary greater than manger gone'));
                    }

                }

            DB::commit ();
        }
        catch (\Exception $exception){
            DB::rollBack ();
            return $this->response()->error(__ ('un known error'))->refresh();
        }

        return $this->response()->success('success')->refresh();

    }

    public function form()
    {
        $this->hidden('id', __('id'))->attribute ('id','vid');
        if ($this->type == 'user'){
            $this->hidden ('type','type')->value ('user');
        }
        else{
            $this->select('type', __('type'))->options ($this->options);
        }
        $this->text('amount', __('amount'))->help (__ ('let it empty to pay total'));
    }

    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu('.$this->id.')" class="btn btn-sm btn-danger salary_action ">pay</a>
<script>
function pu(val) {

  $("#vid").val(val)
}
</script>
';
    }

}
