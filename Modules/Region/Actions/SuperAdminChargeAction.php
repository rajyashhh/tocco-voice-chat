<?php

namespace Modules\Region\Actions;

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
use Modules\Country\Entities\SuperAdmin;
use Illuminate\Validation\ValidationException;

class SuperAdminChargeAction extends Action
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
        $superAdmin = $this->getSuperAdmin($userId);

        return $this->handleUserCharge($request, $superAdmin);
    }

    private function getSuperAdmin($userId)
    {
        return SuperAdmin::where('id', $userId)->first();
    }

    /**
     * @throws \Throwable
     */
    private function handleUserCharge(Request $request, SuperAdmin $superAdmin): Response
    {
        $amount = $request->charge_type == 'increment' ? $request->amount : -$request->amount;
        $authAreaManager = auth()->user();
        $typeCharge = $request->charge_type;

        $userCoins = Cache::rememberForever('super_admin_coins', function () {
            $setting = Setting::where('key', 'super_admin_coins')->first();
            return $setting?->value;
        });

        if ($request->amount_unit === 'usd') {
            $coins = $amount * $userCoins;
        } else {
            $coins = $amount;
        }

        if ($typeCharge === 'increment') {
            if ($authAreaManager->di < abs($coins)) {
                return $this->response()->error(__('You do not have enough coins, please recharge!'))->refresh();
            }
        }

        if ($coins < 0 && $superAdmin->di < abs($amount)) {
            return $this->response()->error(__('Insufficient user balance'))->refresh();
        }

        if (! $userCoins || $userCoins == 0) {
            return $this->response()->error(__('please set super admin coins in configs'))->refresh();
        }

        DB::transaction(function () use ($request, $superAdmin,  $amount, $coins, $typeCharge, $authAreaManager) {

            $amountBefore = $superAdmin->di; //Common::getCurrentBalance($superAdmin->id);

            UserCoinLogHelper::logByType(
                $superAdmin->id,
                $coins,
                $amountBefore,
                UserCoinLogType::ADMIN_CHARGES,
                userType: UserTypeEnum::SUPER_ADMIN,
            );

            $superAdmin->di += $coins;
            if ($superAdmin->di < 0) {
                throw ValidationException::withMessages([
                    'di' => [__('user does not have this coin')],
                ]);
            }

            $superAdmin->save();

            if ($request->charge_type == 'increment') {
                $authAreaManager->di -= abs($coins);
                $authAreaManager->save();
            }

            if ($request->charge_type == 'decrement') {
                $authAreaManager->di += abs($coins);
                $authAreaManager->save();
            }

            $usdAmount = $request->charge_type == 'decrement' ? -$request->amount : $request->amount;
            $this->createChargeRecord($request,  $superAdmin, $amount, $coins, $usdAmount);
        });

        return $this->response()->success('Success')->refresh();
    }

    private function createChargeRecord(Request $request, SuperAdmin $superAdmin, $amount, $coins = 0, $usdAmount): void
    {
        $charge = new Charge();
        $charge->charger_id = Auth::id();
        $charge->charger_type = UserTypeEnum::AREA_MANAGER;
        $charge->user_id = $superAdmin->id;
        $charge->agency_id = null;
        $charge->user_type = UserTypeEnum::SUPER_ADMIN;
        $charge->amount = $coins;
        $charge->usd = $request->amount_unit === 'usd' ? $usdAmount : $usdAmount / Cache::get('super_admin_coins', 1);
        $charge->balance_before =  $superAdmin->di  - $coins;
        $charge->save();

        if ($request->hasFile('invoice')) {
            $imagePath = Common::upload('profile', $request->file('invoice'));
        }

        ChargeInvoice::create([
            'charge_id' => $charge->id,
            'user_id' => $superAdmin->id,
            'reason_en' => $request->reason_en,
            'reason_ar' => $request->reason_ar,
            'invoice' => $imagePath ?? '',
            'type' => UserTypeEnum::SUPER_ADMIN,
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

        $this->image('invoice', __('invoice'))
            ->attribute([
                'id' => 'invoice-field',

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
            toggleInvoiceField();
        SCRIPT);
    }

    public function html(): string
    {
        $title = __('dashboard.add_coins');
        $shippingReports = __('Charge reports');
        $url = url('areaManager/superadmin-charges-report/' . $this->userId . '?name=area_manager');

        $html = '';

        $html .= '<a href="javascript:void(0);" onclick="pu(' . $this->userId . ')" class="charge_action btn btn-sm text-white" style="background-color: #28a745; border-color: #28a745; color: white;">'
            . htmlspecialchars($title) .
            '</a>';

        $html .= '<a href="' . htmlspecialchars($url) . '"
        class="shipping_report btn btn-sm text-white"
        onclick="initDatePickersAfterNav()"
        style="background-color: #b93a0f; border-color: #b93a0f; color: white;">'
            . htmlspecialchars($shippingReports) .
            '</a>';

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
