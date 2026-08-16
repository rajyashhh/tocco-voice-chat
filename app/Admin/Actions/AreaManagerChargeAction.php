<?php

namespace App\Admin\Actions;

use Cache;
use App\Models\Charge;
use App\Helpers\Common;
use App\Models\Setting;

use Illuminate\Http\Request;
use App\Models\ChargeInvoice;
use App\Enums\UserCoinLogType;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Actions\Action;
use App\Helpers\UserCoinLogHelper;
use Encore\Admin\Actions\Response;
use Illuminate\Support\Facades\DB;
use App\Enums\Charges\UserTypeEnum;
use Illuminate\Support\Facades\Auth;
use Modules\Region\Entities\AreaManager;
use Illuminate\Validation\ValidationException;

class AreaManagerChargeAction extends Action
{
    public $name;
    protected $selector = '.charge_action';
    protected $userId;

    public function setUserId($userId): static
    {
        $this->userId = $userId ?? request()->input('userId');
        return $this;
    }

    /**
     * @throws \Throwable
     */
    public function handle(Request $request)
    {
        $userId = $this->userId ?? $request->input('userId');
        $areaManager = $this->getAreaManager($userId);

        return $this->handleUserCharge($request, $areaManager);
    }

    private function getAreaManager($userId)
    {
        return AreaManager::where('id', $userId)->first();
    }

    /**
     * @throws \Throwable
     */
    private function handleUserCharge(Request $request, AreaManager $areaManager): Response
    {
        $amount = $request->charge_type == 'increment' ? $request->amount : -$request->amount;
        $typeCharge = $request->charge_type;

        $userCoins = Cache::rememberForever('zones_coins', function () {
            $setting = Setting::where('key', 'zones_coins')->first();
            return $setting?->value;
        });

        if ($request->amount_unit === 'usd') {
            $coins = $amount * $userCoins;
        } else {
            if (filter_var($request->amount, FILTER_VALIDATE_INT) === false) {
                throw ValidationException::withMessages([
                    'amount' => [__('validation.integer', ['attribute' => __('Amount')])],
                ]);
            }
            $coins = (int) $amount;
        }

        if ($coins < 0 && $areaManager->di < abs($amount)) {
            return $this->response()->error(__('Insufficient user balance'))->refresh();
        }

        if (! $userCoins || $userCoins == 0) {
            return $this->response()->error(__('please set area manager coins in configs'))->refresh();
        }

        DB::transaction(function () use ($request, $areaManager,  $amount, $coins, $typeCharge) {

            $amountBefore = $areaManager->di; //Common::getCurrentBalance($areaManager->id);

            UserCoinLogHelper::logByType(
                $areaManager->id,
                $coins,
                $amountBefore,
                UserCoinLogType::ADMIN_CHARGES,
                userType: UserTypeEnum::AREA_MANAGER,
            );

            $areaManager->di += $coins;
            if ($areaManager->di < 0) {
                throw ValidationException::withMessages([
                    'di' => [__('user does not have this coin')],
                ]);
            }

            $areaManager->save();

            $usdAmount = $request->charge_type == 'decrement' ? -$request->amount : $request->amount;
            $this->createChargeRecord($request,  $areaManager, $amount, $coins, $usdAmount);
        });

        return $this->response()->success('Success')->refresh();
    }

    private function createChargeRecord(Request $request, AreaManager $areaManager, $amount, $coins = 0, $usdAmount): void
    {
        $charge = new Charge();
        $charge->charger_id = Auth::id();
        $charge->charger_type = $request->user_type == 'dash' ? 'dash' : 'dash';
        $charge->user_id = $areaManager->id;
        $charge->agency_id =   null;
        $charge->user_type = UserTypeEnum::AREA_MANAGER;
        $charge->amount = $coins;
        $charge->usd = $request->amount_unit === 'usd' ? $usdAmount : $usdAmount / Cache::get('zones_coins', 1);
        $charge->balance_before =  $areaManager->di  - $coins;
        $charge->save();

        if ($request->hasFile('invoice')) {
            $imagePath = Common::upload('profile', $request->file('invoice'));
        }

        ChargeInvoice::create([
            'charge_id' => $charge->id,
            'user_id' => $areaManager->id,
            'reason_en' => $request->reason_en,
            'reason_ar' => $request->reason_ar,
            'invoice' => $imagePath ?? '',
            'type' => UserTypeEnum::AREA_MANAGER,
        ]);
    }

    public function form(): void
    {
        $this->name = __('Charge');
        $this->hidden('userId')->attribute('id', 'vid');
        $this->select('charge_type', __('Charge Type'))->options(['increment' => __('increment'), 'decrement' => __('decrement')])->default('increment');

        $this->select('amount_unit', __('Amount Unit'))
            ->options([
                'usd'   => __('Dollar'),
                'coins' => __('Coins'),
            ])
            ->default('usd')
            ->help(__('Choose whether the entered amount is in USD or Coins'));

        $this->text('amount', __('Amount'))
            ->rules('numeric|gt:0')
            ->addElementClass('price-input')
            ->help(__('Enter the amount based on the selected type'));

        $this->text('reason_en', __('reason en'));
        $this->text('reason_ar', __('reason ar'));

        $this->select('form', __('add invoice'))
            ->options([
                0 => __('no'),
                1 => __('yes'),
            ])
            ->attribute(['id' => 'form-select']);

        $this->text('invoice', __('invoice'))
            ->attribute([
                'id' => 'invoice-field',
                'type' => 'file',
                'accept' => 'image/*',
                'style' => 'padding: 10px; background: transparent; border: 2px dashed #4a5568; border-radius: 12px; cursor: pointer; color: #a0aec0;',
            ]);

        $this->hidden('amount_type')->value(1);

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

            $(document).on('shown.bs.modal', function() {
                setTimeout(toggleInvoiceField, 100);
            });

            toggleInvoiceField();
        SCRIPT);
    }

    public function html(): string
    {
        $title = __('dashboard.add_coins');
        $shippingReports = __('Charge reports');
        $url = url('admin/area-manager-charges-report/' . $this->userId);

        $html = '';

        if (Admin::user()->can('add-switch-charge-to-user') || Admin::user()->can('*')) {
            $html .= '<a href="javascript:void(0);" onclick="pu(' . $this->userId . ')" class="charge_action btn btn-sm btn-success">'
                . htmlspecialchars($title) .
                '</a>';
            $html .= '&nbsp;&nbsp;';
        }

        if (Admin::user()->can('history-switch-charge-to-user') || Admin::user()->can('*')) {
            $html .= '<a href="' . htmlspecialchars($url) . '"
            class="shipping_report btn btn-sm btn-danger"
            onclick="initDatePickersAfterNav()">'
                . htmlspecialchars($shippingReports) .
                '</a>';
        }

        $html .= <<<HTML
            <script>
            function pu(val) {
                $("#vid").val(val);
            }

            function initDatePickersAfterNav() {
                setTimeout(function() {
                    $('.form-control[id$="_date"]').datetimepicker({
                        format: 'YYYY-MM-DD'
                    });
                }, 500);
            }

            $(document).on('pjax:complete', function() {
                initDatePickersAfterNav();
            });
            </script>
            HTML;

        return $html;
    }
}
