<?php

namespace App\ShippingAdmin\Controllers;

use App\Admin\Controllers\MainController;
use App\Helpers\ShippingAgencyHelper;
use App\Helpers\ShippingScopeHelper;
use App\Models\ShippingAdminTransaction;
use App\Models\ShippingSuperAdmin;
use App\Services\ShippingSuperAdminWalletService;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The Shipping Super Admin's own wallet screen.
 *
 * index(): a coin card (di, read directly) plus the layer's ledger.
 * charge(): the ONLY downward money path — shipping super admin -> shipping
 *           agency, coin-only, delegated to the hardened wallet service.
 *
 * There is deliberately no upward/self funding here: this actor is funded solely
 * by its Country Manager (see App\ShippingAdmin\Controllers\CountryManagerFundingController).
 */
class WalletController extends MainController
{
    protected $title = 'Shipping Wallet';

    public $permission_name = 'shipping-super-admin-wallet';

    public function index(Content $content)
    {
        $coins = (int) (Auth::user()->di ?? 0);

        return $content
            ->header(trans('admin.index'))
            ->row(function ($row) use ($coins) {
                $row->column(12, view('shippingAdmin::wallet.card', ['coins' => $coins]));
            })
            ->row(function ($row) {
                $row->column(12, $this->grid());
            });
    }

    protected function grid()
    {
        $grid = new Grid(new ShippingAdminTransaction());

        $me = Auth::user()->id;

        // Show every leg this actor is party to (funded in, charged out).
        $grid->model()
            ->where(function ($q) use ($me) {
                $q->where(function ($sub) use ($me) {
                    $sub->where('sender_type', 'shipping_super_admin')->where('sender_id', $me);
                })->orWhere(function ($sub) use ($me) {
                    $sub->where('receiver_type', 'shipping_super_admin')->where('receiver_id', $me);
                });
            })
            ->orderByDesc('id');

        $grid->column('id', __('Id'));

        $grid->column('type', __('Type'))->display(function ($type) {
            $labels = [
                ShippingAdminTransaction::FUND_IN     => __('Funded (in)'),
                ShippingAdminTransaction::CHARGE_OUT  => __('Charged agency (out)'),
                ShippingAdminTransaction::FUND_OUT    => __('Fund (out)'),
                ShippingAdminTransaction::CHARGE_IN   => __('Charge (in)'),
            ];

            return $labels[$type] ?? $type;
        });

        $grid->column('coins', __('Coins'))->display(fn ($v) => number_format((int) $v));
        $grid->column('receiver_type', __('Receiver'));
        $grid->column('receiver_id', __('Receiver Id'));
        $grid->column('after_amount', __('Balance After'))->display(fn ($v) => number_format((int) $v));

        $grid->column('created_at', __('Created at'))->display(function ($v) {
            return \Carbon\Carbon::parse($v)->format('Y-m-d H:i');
        });

        $grid->disableCreateButton();
        $grid->disableActions();
        $grid->disableRowSelector();
        $grid->disableFilter();

        return $grid;
    }

    /**
     * Charge a shipping agency (coin-only). The sender is the authenticated
     * actor; the receiver must be in scope (same country). The operation_uuid
     * from the form makes retries idempotent.
     */
    public function charge(Request $request)
    {
        try {
            $data = $request->validate([
                'amount'         => 'required|integer|min:1',
                'target_id'      => 'required|integer',
                'operation_uuid' => 'required|string|max:64',
            ]);

            if (Auth::user()->is_frozen_wallet) {
                throw new \RuntimeException(__('your wallet frozen.'));
            }

            $sender = ShippingSuperAdmin::findOrFail(Auth::id());

            $agency = ShippingScopeHelper::chargeableAgency($sender, $data['target_id']);
            if (!$agency) {
                throw new \RuntimeException(__('This agency not found'));
            }
            if ($agency->is_frozen == 1) {
                throw new \RuntimeException(__('it_agency_freez_charge'));
            }
            if (!ShippingAgencyHelper::isVerifiedChargeForAgency($agency)) {
                throw new \RuntimeException(__('not_verified_agency'));
            }

            $applied = app(ShippingSuperAdminWalletService::class)->chargeAgency(
                $sender,
                $agency,
                (int) $data['amount'],
                $data['operation_uuid']
            );

            admin_toastr($applied ? __('Charged successfully') : __('Already processed'), 'success');

            return back();
        } catch (\Throwable $e) {
            admin_toastr($e->getMessage(), 'error');

            return back();
        }
    }
}