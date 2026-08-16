<?php

namespace App\Admin\Actions;

use App\Enums\UserCoinLogType;
use App\Helpers\Common;
use App\Helpers\UserCoinLogHelper;
use App\Models\User;
use App\Models\Charge;
use App\Models\Setting;
use App\Helpers\UserCommon;
use Illuminate\Http\Request;
use App\Models\ChargeInvoice;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Actions\Action;
use Illuminate\Support\Facades\DB;
use App\Facades\CustomNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Public\Http\Services\UserCounterServices;

class UsersChargeAction extends Action
{
    public $name;
    protected $selector = '.charge_action';
    protected $userId;

    public function setUserId($userId): static
    {
        $this->userId = $userId ?? request()->input('userId');
        return $this;
    }

    public function handle(Request $request)
    {
        $userId = $this->userId ?? $request->input('userId');
        $user = $this->getUser($userId);
        // if (!$user) {
        //     return $this->response()->error(__('api_responses.agency'))->refresh();
        // }
        // if ($user->is_frozen == 1) {
        //     return $this->response()->error(__('frozen'))->refresh();
        // }
        return $this->handleUserCharge($request, $user);
    }


    private function getUser($userId)
    {
        return User::where('id', $userId)->first();
    }

    /**
     * @throws \Throwable
     */
    private function handleUserCharge(Request $request, User $user)
    {
        $amount = $request->charge_type == 'increment' ? $request->amount : -$request->amount;
        $typeCharge = $request->charge_type;

        if ($amount < 0 && $user->di < abs($amount)) {
            return $this->response()->error(__('Insufficient user balance'))->refresh();
        }
        $userCoins = \Cache::rememberForever('user_coins', function () {
            $setting =   Setting::where('key', 'user_coins')->first();
            return $setting?->value;
        });
        
        $coins = $amount * $userCoins;
        
        if (! $userCoins || $userCoins == 0) {
            return $this->response()->error(__('please set user coins in configs'))->refresh();
        }
        
        DB::transaction(function () use ($request, $user,  $amount, $coins, $typeCharge) {
            
            $amountBefore =  Common::getCurrentBalance($user->id);

            UserCoinLogHelper::logByType(
                $user->id,
                $coins,
                $amountBefore,
                UserCoinLogType::ADMIN_CHARGES,
            );

            $user->di += $coins;
            if ($user->di < 0) {
                throw ValidationException::withMessages([
                    'di' => [__('user does not have this coin')],
                ]);
            }
            $user->save();

            $usdAmount = $request->charge_type == 'decrement' ? -$request->amount : $request->amount;
            $this->createChargeRecord($request,  $user, $amount, $coins, $usdAmount);

            if ($typeCharge == "increment") {
                $admin = Auth::user()->username ?? 'Admin';
                if ($user->owner) CustomNotification::chargeAction($user, $request, $admin);
                UserCommon::addChargeLevel($user->id, $amount);
            }
        });

        $title = $typeCharge == 'increment' ? 'Coins Added' : 'Coins Deducted';

        $body = $typeCharge === 'increment'
            ? 'You have received :coins coins from admin.'
            : ':coins coins were deducted from your account by admin.';


        CustomNotification::charges($user, $title, $body, ['coins' => $coins]);

        return $this->response()->success('Success')->refresh();
    }



    private function createChargeRecord(Request $request, User $user, $amount, $coins = 0, $usdAmount)
    {
        $charge = new Charge();
        $charge->charger_id = Auth::id();
        $charge->charger_type = $request->user_type == 'dash' ? 'dash' : 'dash';
        $charge->user_id = $user->id;
        $charge->agency_id =   null;
        $charge->user_type = 'user';
        $charge->amount = $coins;
        $charge->usd = $usdAmount;
        $charge->balance_before =  $user->di  - $coins;
        $charge->save();

        UserCommon::UserEarnedInvitation($user->id, $coins,$charge->id);

        if ($request->hasFile('invoice')) {
            $imagePath = Common::upload('profile', $request->file('invoice'));
        }

        ChargeInvoice::create([
            'charge_id' => $charge->id,
            'user_id' => $user->id,
            'reason_en' => $request->reason_en,
            'reason_ar' => $request->reason_ar,
            'invoice' => $imagePath ?? '',
            'type' => 'user',
        ]);
    }

    public function form()
    {
        $this->name = __('Charge');
        $this->hidden('userId')->attribute('id', 'vid');
        $this->select('charge_type', __('Charge Type'))->options(['increment' => __('increment'), 'decrement' => __('decrement')])->default('increment');
        $this->text('amount', __('Amount'))
            ->rules('numeric|gt:0')
            ->addElementClass('price-input')
            ->help(__('Enter amount in dollars'));
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

    public function html()
    {
        $title = __('dashboard.add_coins');
        $shippingReports = __('Charge reports');
        $url = url('admin/user-charges-report/' . $this->userId);

        $html = '';

        if (Admin::user()->can('add-switch-charge-to-user') || Admin::user()->can('*')) {
            $html .= '<a href="javascript:void(0);" onclick="pu(' . $this->userId . ')" class="charge_action btn btn-sm text-white" style="background-color: #28a745; border-color: #28a745; color: white;">'
                . htmlspecialchars($title) .
                '</a>';
        }

        if (Admin::user()->can('history-switch-charge-to-user') || Admin::user()->can('*')) {
            $html .= '<a href="' . htmlspecialchars($url) . '"
            class="shipping_report btn btn-sm text-white"
            onclick="initDatePickersAfterNav()"
            style="background-color: #b93a0f; border-color: #b93a0f; color: white;">'
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
