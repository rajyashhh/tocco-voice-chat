<?php

namespace App\Admin\Actions;

use App\Models\ShippingAgency;
use App\Models\User;
use App\Models\Agency;
use App\Models\Charge;
use Encore\Admin\Form;
use App\Helpers\Common;
use App\Models\Setting;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Auth;
use Modules\Achievement\Http\Services\UserAchievementService;

class ChargeAction extends Action
{
    public $name;
    protected $selector = '.charge_action';
    protected $agencyId;

    public function setAgencyId($agencyId): static
    {
        $this->agencyId = $agencyId;
        return $this;
    }

    public function handle(Request $request)
    {
        //        if ($request->user_type != 'dash') {
        //            $user = $this->getUser($request);
        //            if (!$user) {
        //                return $this->response()->error(__('user not found'))->refresh();
        //            }
        //
        //            if ($this->isInvalidAmount($request->amount)) {
        //                return $this->response()->error(__('amount must be more than 10'))->refresh();
        //            }
        //        }

        //        if ($request->user_type == 'dash') {
        $agency = $this->getAgency($request->agency_id);
        if (!$agency) {
            return $this->response()->error(__('api_responses.agency'))->refresh();
        }
        if ($agency->is_frozen == 1) {
            return $this->response()->error(__('frozen'))->refresh();
        }
        $user = $agency->owner;
        if (!$user)  return $this->response()->error(__('this agency not have owner'))->refresh();
        return $this->handleAgencyCharge($request, $agency, $user);

        //        }
        //        return $this->handleUserCharge($request, $user);
    }

    private function getUser(Request $request)
    {
        //        if ($request->user_type == 'dashdash') {
        //            return $request->id_type == '1'
        //                ? User::query()->searchByUuid($request->user_id)->first()
        //                : User::query()->find($request->user_id);
        //        }

        return $request->id_type == '1'
            ? User::query()->where('uuid', $request->user_id)->first()
            : User::query()->find($request->user_id);
    }

    private function getAgency($agencyId)
    {
        return ShippingAgency::where("id", $agencyId)->first();
    }

    private function isInvalidAmount($amount)
    {
        return $amount < 10;
    }

    private function handleAgencyCharge(Request $request, Agency $agency, User $user)
    {
        $amount = $request->charge_type == 'increment' ? $request->amount : -$request->amount;

        if ($amount < 0 && $agency->coins < abs($amount)) {
            return $this->response()->error(__('Insufficient agency balance'))->refresh();
        }
        //        $oneUsdValueForOneCoin = Common::getConf('one_usd_value_in_coins');
        //        if (! $oneUsdValueForOneCoin || $oneUsdValueForOneCoin == 0){
        //            return $this->response()->error(__('please set usd_value_in_coins in configs'))->refresh();
        //        }

        $shippingCoins = \Cache::rememberForever('shipping_coins', function () {
            $setting =   Setting::where('key', 'shipping_coins')->first();
            return $setting?->value;
        });
        if (! $shippingCoins || $shippingCoins == 0) {
            return $this->response()->error(__('please set agency coins in configs'))->refresh();
        }

        DB::transaction(function () use ($request, $agency, $user, $amount, $shippingCoins) {
            // agencies.coins is an integer column; floor the rate product so any
            // fractional shipping_coins can only ever round toward the platform.
            $coins = (int) floor($amount * $shippingCoins);

            $agency->coins += $coins;
            if ($agency->coins < 0)  return $this->response()->error(__('agency does not have this coin'))->refresh();
            $agency->save();

            $usdAmount = $request->charge_type == 'decrement' ? -$request->amount : $request->amount;
            $this->createChargeRecord($request, $user, $agency, $amount, $coins, $usdAmount);

            if ($request->charge_type == "increment") {
                $admin = Auth::user()->username ?? 'Admin';
                CustomNotification::chargeAction($user, $request, $admin);
            }
        });

        return $this->response()->success('Success')->refresh();
    }

    private function handleUserCharge(Request $request, User $user)
    {
        //        $percentage = Common::getConf("special_transfer_to_usd") ?? 1;
        //        $usdAmount = $request->amount / $percentage;

        $shippingCoins = \Cache::rememberForever('shipping_coins', function () {
            $setting =   Setting::where('key', 'shipping_coins')->first();
            return $setting?->value;
        });
        //        $oneUsdValueForOneCoin = Common::getConf('one_usd_value_in_coins');
        $usdAmountRaw = $request->charge_type == 'decrement' ? -$request->amount : $request->amount;
        $usdAmount = $usdAmountRaw * $shippingCoins;

        DB::transaction(function () use ($request, $user, $usdAmount, $usdAmountRaw) {
            $amount = $request->charge_type == 'increment' ? $request->amount : -$request->amount;
            if ($amount < 0 && $user->di < abs($amount)) {
                return $this->response()->error(__('Insufficient user balance'))->refresh();
            }

            $user->di += $amount;
            $user->save();
            if ($request->charge_type == "increment") {
                $admin = Auth::user()->username ?? 'Admin';
                CustomNotification::chargeAction($user, $request, $admin);
            }
            $this->createChargeRecord($request, $user, null, $amount, $usdAmount, $usdAmountRaw);

            (new UserAchievementService())->insertCharging($user, $request->amount);
        });

        return $this->response()->success('Success')->refresh();
    }

    private function createChargeRecord(Request $request, User $user, ?Agency $agency, $amount, $coins = 0, $usdAmount)
    {

        //        $shippingCoins = cache()->get('shipping_coins');
        $charge = new Charge();
        $charge->charger_id = Auth::id();
        $charge->charger_type = $request->user_type == 'dash' ? 'dash' : 'dash';
        $charge->user_id = $agency->id;
        $charge->agency_id = $agency->id ?? null;
        $charge->user_type = 'agency';
        $charge->amount = $coins;
        $charge->usd = $usdAmount;
        $charge->balance_before = ($agency ? $agency->coins : $user->di) - $amount;
        //dd($charge);
        $charge->save();
        UserCommon::UserEarnedInvitation($user->id, $amount ,$charge->id);
    }

    public function form()
    {
        $this->name = __('Charge');
        $this->hidden('agency_id')->attribute('id', 'vid');
        // $this->hidden('charger_type')->value('dash');
        //        $this->text('user_id', __('User ID / Agency ID'));
        //        $this->select('id_type', __('ID Type'))->options([0 => __('Normal'), 1 => __('Uuid')]);
        $this->select('charge_type', __('Charge Type'))->options(['increment' => __('increment'), 'decrement' => __('decrement')])->default('increment');
        //        $this->select('user_type', __('User Type'))->options(['dashdash' => __('App'), 'dash' => __('Agencies')])->default('dashdash'); //dashdash
        $this->text('amount', __('Amount'))
            ->addElementClass('price-input')
            ->help(__('Enter amount in dollars'));
        $this->hidden('amount_type')->value(1);
    }

    public function html()
    {
        $title = __('dashboard.add_coins');
        $shippingReports = __('Charge reports');
        $url = url('admin/charge-reports/' . $this->agencyId);

        return <<<HTML
                <a href="javascript:void(0);" onclick="pu({$this->agencyId})" class="charge_action btn btn-sm text-white" style="background-color: #28a745; border-color: #28a745; color: white;">
                    {$title}
                </a>

                <a href="{$url}" class="shipping_report btn btn-sm text-white" style="background-color: #b93a0f; border-color: #b93a0f; color: white;">
                    {$shippingReports}
                </a>

                <script>
                function pu(val) {
                    $("#vid").val(val);
                }
                </script>
                HTML;
    }
}
