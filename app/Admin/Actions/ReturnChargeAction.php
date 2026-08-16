<?php

namespace App\Admin\Actions;




use App\Enums\UserCoinLogType;
use App\Helpers\UserCoinLogHelper;
use App\Models\User;
use App\Models\Charge;
use App\Models\ReturnCharge;
use App\Models\ShippingAgency;
use App\Models\UserSallary;
use Encore\Admin\Actions\Action;
use Auth;



class ReturnChargeAction extends Action
{

    public $id;

    protected $selector = '.salary_action';


    public function __construct($id = 0)
    {

        $this->id = $id;
        parent::__construct();
    }
    public function handle(\Illuminate\Http\Request $request)
    {

        $charge = Charge::Find($request->id);

        $returned   =  ReturnCharge::where('charge_id', $charge->inflate_add)->exists();
        if ($returned) return $this->response()->error(__('returned before'));
        if ($charge->charger_type === 'user') {
            $user = User::find($charge->charger_id);
            $userSalary = UserSallary::where('user_id', $user->id)->orderByDesc('id')->first();

            if ($userSalary) {
                $userSalary->decrement('cut_amount', (int) $charge->usd);
            }

            if ($charge->user_type == 'agency') {
                $agency = ShippingAgency::find($charge->user_id);
                if (!$agency) return $this->response()->error(__('agency not found'));

                $data =       [
                    'admin_id' => Auth::id(),
                    'coins'    => $charge->amount,
                    'usd'      => $charge->usd,
                    'charger_id'  => $user->id,
                    'charger_type' => 'user',
                    'receiver_id' => $agency->id,
                    'receiver_type' => 'agency',
                    'receiver_amount' => $agency->coins,
                    'charge_id'    => $charge->id,
                ];
                $agency->decrement('coins', $charge->amount);
            } elseif ($charge->user_type == 'user') {
                $receiverUser = User::find($charge->user_id);
                if (!$receiverUser) return $this->response()->error(__('user not found'));

                $data =       [
                    'admin_id' => Auth::id(),
                    'coins'    => $charge->amount,
                    'usd'      => $charge->usd,
                    'charger_id'  => $user->id,
                    'charger_type' => 'user',
                    'receiver_id' => $receiverUser->id,
                    'receiver_type' => 'user',
                    'receiver_amount' => $receiverUser->di,
                    'charge_id'    => $charge->id,
                ];

                $amountBefore =  $receiverUser->di;
                UserCoinLogHelper::logByType(
                    $receiverUser->id,
                    -abs($charge->amount),
                    $amountBefore,
                    UserCoinLogType::RETURN_CHAGE,
                );
                

                $receiverUser->decrement('di', $charge->amount);
            }
        }
        // elseif ($charge->charger_type === 'agency' &&  $charge->user_type === 'user') {
        //     $agency = ShippingAgency::find($charge->charger_id);
        //     $user = User::find($charge->user_id);

        //     $user->decrement('di', $charge->amount);
        //     $agency->increment('coins', $charge->amount);
        // }
        else {
            return $this->response()->error(__('not shipping agency'));
        }



        ReturnCharge::create($data);

        return $this->response()->success('success')->refresh();
    }


    public function form()
    {
        $this->hidden('id', __('id'))->attribute('id', 'vid');

        $this->confirm(__('messages.confirm_delete'), __('messages.returnCharge'), [
            'icon' => 'warning',
            'showCancelButton' => true,
            'confirmButtonText' => __('messages.yes'),
            'cancelButtonText' => __('messages.cancel'),
        ]);
    }


    public function html()
    {
        return '<a href="javascript:void(0);" onclick="pu(' . $this->id . ')" class="btn btn-sm btn-info salary_action ">' . __('return') . '</a>
        <script>
        function pu(val) {

        $("#vid").val(val)
        }
        </script>
        ';
    }
}
