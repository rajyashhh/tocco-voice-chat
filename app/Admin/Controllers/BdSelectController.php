<?php

namespace App\Admin\Controllers;

use App\Models\Bd;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Encore\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Admin\Actions\MakeBdDefultAction;
use App\Admin\Controllers\MainController;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use App\Admin\Widgets\InfoBox;
use App\Models\Setting;
use Encore\Admin\Facades\Admin;




/**
 * Unified "Super Admin Settings" page (owner 2026-08-10). One page carrying the
 * "Freeze Wallet" kill-switch for BOTH the BD layer (dollar wallet) and the
 * Shipping Super Admin layer (coin wallet). Each switch is a GLOBAL toggle,
 * matching the pre-existing BD pattern:
 *   - BD:       settings key `bd_stop_charge` (unchanged), consumed by
 *               App\Bd\Controllers\WalletController via Common::stopSwitch.
 *   - Shipping: settings key `shipping_super_admin_stop_charge` (new sibling),
 *               consumed by App\Services\ShippingSuperAdminWalletService::chargeAgency
 *               via Common::stopSwitch.
 *
 * Both switches read AND write the settings DB table (via the Setting model, so
 * the SettingObserver flushes the rememberForever cache) — one store, so a fresh
 * clone/demo defaults to NOT frozen. The page and the toggle are gated on the
 * super admin (can('*')) because these are platform-wide, country-unscoped,
 * cross-layer money kill-switches, not per-layer BD record edits.
 *
 * The class/route uri (`usersBd-settings`) is intentionally kept so existing
 * admin_role_menu bindings and the resource route survive; only the surface is
 * relabeled to "Super Admin Settings".
 */
class BdSelectController extends MainController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Super Admin Settings';
    public $permission_name = 'BD';

    public function index(Content $content)
    {
        // This page carries PLATFORM-WIDE, country-unscoped, cross-layer money
        // kill-switches (BD dollar wallet + Shipping coin wallet). It is not a BD
        // record screen, so it must NOT be visible under `browse-BD` (a per-layer
        // writer permission). Only the super admin (can('*')) may see or touch it,
        // otherwise a BD-scoped writer could freeze/unfreeze the whole platform's
        // shipping charge path (horizontal escalation across layers).
        if (!Admin::user()->can('*')) {
            abort(403, __('Unauthorized access'));
        }

        return $content
            ->title(trans('Super Admin Settings'))
            ->body($this->grid2());
    }





    protected function grid2()
    {
        // Read both global freeze switches the same fail-closed way the money
        // paths read them, so the UI reflects the true engaged state (a missing
        // key reads as frozen, matching Common::stopSwitch).
        $bdFrozen = \App\Helpers\Common::stopSwitch('bd_stop_charge');
        $shippingFrozen = \App\Helpers\Common::stopSwitch(
            \App\Services\ShippingSuperAdminWalletService::SHIPPING_STOP_CHARGE_KEY
        );

        $box1 = new Box(__('Super Admin Settings'), view('admin.grid.bd.selectPage', compact('bdFrozen', 'shippingFrozen')));

        return $box1->render();
    }



//    /**
//     * Show interface.
//     *
//     * @param mixed $id
//     * @param Content $content
//     * @return \Illuminate\Http\RedirectResponse
//     */
//
//     public function makeDefault(Request $request)
//    {
//        $bdId = $request->input('bd_id');
//
//        Bd::query()->update(['default' => false]);
//
//        $bd = Bd::findOrFail($bdId);
//        $bd->default = true;
//        $bd->save();
//
//        admin_success('تم التحديث', 'تم تعيين BD الافتراضي بنجاح');
//
//        return redirect()->back();
//
//    }


    /**
     * Toggle a global "Freeze Wallet" switch for one layer. `layer` selects which
     * settings key is written (whitelisted). The write goes to the settings DB
     * table via the Setting model (single source of truth), and the money paths
     * read it back via Common::stopSwitch. `enabled=1` means FROZEN (wallet
     * stopped), matching the switch label semantics on the page.
     */
    public function toggleSalaryTransfer(Request $request)
    {
        // Same authority as the page: only the super admin may flip a global,
        // country-unscoped money kill-switch. `edit-BD` is a per-layer writer
        // permission and MUST NOT grant control over the shipping (coin) layer
        // nor let a BD writer undo a freeze the super admin set.
        if (!Admin::user()->can('*')) {
            abort(403, __('Unauthorized access'));
        }

        $validated = $request->validate([
            'layer'   => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $key = $this->freezeKeyForLayer($validated['layer']);
        if ($key === null) {
            return response()->json([
                'status'  => 'error',
                'message' => __('This value is not allowed'),
            ], 422);
        }

        $frozen = (bool) $validated['enabled'];

        // Write through the Setting model (not settings()->set / JSON) so the
        // SettingObserver fires and flushes the rememberForever cache for this
        // key. This keeps ONE store (settings DB table) as the single source of
        // truth for both read and write, so a clone/demo defaults to NOT frozen.
        \App\Helpers\Common::withMoneyKeyWrite(fn() => Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $frozen ? '1' : '0']
        ));

        // Common::stopSwitch reads DB OR JSON. The original BD toggle wrote the
        // JSON store, so a stale JSON '1' could shadow a DB '0' and keep the
        // wallet frozen forever. Drop the JSON copy so the DB row is the sole
        // authority for this key going forward.
        settings()->remove($key);

        return response()->json([
            'status'  => 'success',
            'message' => $frozen
                ? __('Wallet frozen — charging is disabled.')
                : __('Wallet unfrozen — charging is enabled.'),
        ]);
    }

    /**
     * Whitelist map layer -> settings key. Fail-closed: an unknown layer returns
     * null so the caller rejects it (never writes an arbitrary key from input).
     */
    private function freezeKeyForLayer(string $layer): ?string
    {
        return match ($layer) {
            'bd'       => 'bd_stop_charge',
            'shipping' => \App\Services\ShippingSuperAdminWalletService::SHIPPING_STOP_CHARGE_KEY,
            default    => null,
        };
    }

}
