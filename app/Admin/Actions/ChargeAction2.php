<?php

namespace App\Admin\Actions;

use App\Models\Charge;
use App\Helpers\Common;
use App\Models\Setting;
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

class ChargeAction2 extends Action
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
        if (!Admin::user()->can('add-switch-' . 'coin-recharge') && !Admin::user()->can('*')) {
            return $this->response()->error(__('you dont have permission'))->refresh();
        }
        $agency = $this->getAgency($request->agency_id);
        if (!$agency) {
            return $this->response()->error(__('api_responses.agency'))->refresh();
        }
        if (!Common::isCountryInAdminScope($agency->country_id)) {
            return $this->response()->error(__('you dont have permission'))->refresh();
        }
        if ($agency->is_frozen == 1) {
            return $this->response()->error(__('frozen'))->refresh();
        }
        return $this->handleAgencyCharge($request, $agency);
    }


    private function getAgency($agencyId)
    {
        return ShippingAgency::where("id", $agencyId)->first();
    }

    private function handleAgencyCharge(Request $request, ShippingAgency $agency)
    {
        $amount = $request->charge_type == 'increment' ? $request->amount : -$request->amount;

        if ($amount < 0 && $agency->coins < abs($amount)) {
            return $this->response()->error(__('Insufficient agency balance'))->refresh();
        }
        $shippingCoins = \Cache::rememberForever('shipping_coins', function () {
            $setting =   Setting::where('key', 'shipping_coins')->first();
            return $setting?->value;
        });
        if (! $shippingCoins || $shippingCoins == 0) {
            return $this->response()->error(__('please set agency coins in configs'))->refresh();
        }

        DB::transaction(function () use ($request, $agency,  $amount, $shippingCoins) {
            // agencies.coins is an integer column; floor the rate product so any
            // fractional shipping_coins can only ever round toward the platform.
            $coins = (int) floor($amount * $shippingCoins);

            $agency->coins += $coins;
            if ($agency->coins < 0) {
                throw ValidationException::withMessages([
                    'coins' => [__('agency does not have this coin')],
                ]);
            }
            $agency->save();

            $usdAmount = $request->charge_type == 'decrement' ? -$request->amount : $request->amount;
            $this->createChargeRecord($request,  $agency, $amount, $coins, $usdAmount, $shippingCoins);

            if ($request->charge_type == "increment") {
                $admin = Auth::user()->username ?? 'Admin';
                if ($agency->owner) CustomNotification::chargeAction($agency->owner, $request, $admin, $agency, $coins);
            }
        });

        return $this->response()->success('Success')->refresh();
    }



    private function createChargeRecord(Request $request, ShippingAgency $agency, $amount, $coins = 0, $usdAmount, $shippingCoins)
    {

        $charge = new Charge();
        $charge->charger_id = Auth::id();
        $charge->charger_type = $request->user_type == 'dash' ? 'dash' : 'dash';
        $charge->user_id = $agency->id;
        $charge->agency_id = $agency->id ?? null;
        $charge->user_type = 'agency';
        $charge->amount = $coins;
        $charge->usd = $usdAmount;
        $charge->balance_before =  $agency->coins  - $coins;

        $charge->save();
        if ($request->hasFile('invoice')) {
            $imagePath = Common::upload('profile', $request->file('invoice'));
        }
        ChargeInvoice::create([
            'charge_id' => $charge->id,
            'user_id' => $agency->id,
            'reason_en' => $request->reason_en,
            'reason_ar' => $request->reason_ar,
            'invoice' => $imagePath ?? '',
            'type' => 'agency',
        ]);
    }


    public function form()
    {
        $this->hidden('agency_id')->attribute(['id' => 'vid']);

        $this->select('charge_type', __('Charge Type'))
            ->options([
                'increment' => __('increment'),
                'decrement' => __('decrement'),
            ])
            ->default('increment');

        $this->text('amount', __('Amount'))
            ->addElementClass('price-input')
            ->help(__('Enter amount in dollars'))->rules('required|numeric|gt:0');

        $this->text('reason_en', __('reason en'));
        $this->text('reason_ar', __('reason ar'));

        $this->select('form', __('add invoice'))
            ->options([
                0 => __('no'),
                1 => __('yes'),
            ])
            ->attribute(['id' => 'form-select']);

        $this->image('invoice', __('invoice'))
            ->attribute([
                'id' => 'invoice-field',

            ]);

        $this->hidden('amount_type')->value(1);

        // ✅ JS to toggle invoice field inside modal
        Admin::script(<<<'SCRIPT'
            function toggleInvoiceField() {
                var selected = $('#form-select').val();
                if (selected === '1') {
                    $('#invoice-field').closest('.form-group').show();
                } else {
                    $('#invoice-field').closest('.form-group').hide();
                }
            }

            $(document).off('change', '#form-select').on('change', '#form-select', toggleInvoiceField);
            toggleInvoiceField();
        SCRIPT);
    }


    function html()
    {
        $title = __('dashboard.add_coins');
        $shippingReports = __('Charge reports');
        $url = url('admin/charge-reports/' . $this->agencyId);

        $html = '';

        //if (Admin::user()->can('add-switch-' .'coin-recharge') || Admin::user()->can('*')) {
        $html .= '<a href="javascript:void(0);" onclick="pu(' . $this->agencyId . ')" class="charge_action btn btn-sm text-white" style="background-color: #28a745; border-color: #28a745; color: white;">'
            . htmlspecialchars($title) .
            '</a>';
        // }

        if (Admin::user()->can('charge-report-switch-' . 'coin-recharge') || Admin::user()->can('*')) {

            $html .= '<a href="' . htmlspecialchars($url) . '" class="shipping_report btn btn-sm text-white" style="background-color: #b93a0f; border-color: #b93a0f; color: white;">'
                . htmlspecialchars($shippingReports) .
                '</a>';
        }

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
