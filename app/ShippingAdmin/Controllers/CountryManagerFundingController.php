<?php

namespace App\ShippingAdmin\Controllers;

use App\Helpers\ShippingScopeHelper;
use App\Http\Controllers\Controller;
use App\Services\ShippingSuperAdminWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Country\Entities\SuperAdmin;

/**
 * The single legitimate funding path INTO the shipping super admin layer:
 * Country Manager -> Shipping Super Admin (coin-only, di -> di).
 *
 * This endpoint is served under the country-manager (/superadmin) portal because
 * the sender is a country manager, but the handler and its money engine live in
 * the shipping layer it feeds. The sender is always Auth::id(); the request only
 * names the target and amount. Scope is enforced fail-closed: a country manager
 * can fund only its own child shipping super admins inside its own country.
 */
class CountryManagerFundingController extends Controller
{
    public function fund(Request $request)
    {
        try {
            $data = $request->validate([
                'amount'         => 'required|integer|min:1',
                'target_id'      => 'required|integer',
                'operation_uuid' => 'required|string|max:64',
            ]);

            $countryManager = SuperAdmin::findOrFail(Auth::id());

            if ($countryManager->is_frozen_wallet) {
                throw new \RuntimeException(__('your wallet frozen.'));
            }
            if ($countryManager->transfer_salary == 1) {
                throw new \RuntimeException(__('api_responses.freeze_transfer_charger'));
            }

            $target = ShippingScopeHelper::fundableShippingSuperAdmin($countryManager, $data['target_id']);
            if (!$target) {
                throw new \RuntimeException(__('This sub admin not found under your account.'));
            }

            $applied = app(ShippingSuperAdminWalletService::class)->fundFromCountryManager(
                $countryManager,
                $target,
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