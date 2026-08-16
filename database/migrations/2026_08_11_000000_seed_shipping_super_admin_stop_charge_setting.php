<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds a baseline value for the shipping-super-admin global freeze switch
 * (`shipping_super_admin_stop_charge`, the coin counterpart of `bd_stop_charge`).
 *
 * WHY THIS IS REQUIRED: the money paths read this switch through
 * App\Helpers\Common::stopSwitch, which is FAIL-CLOSED — a key that is missing
 * from BOTH the settings DB table AND storage/app/settings.json reads as ENGAGED
 * (frozen). Without this baseline row a fresh clone (and the demo) would have the
 * shipping super admin's charge path frozen by default. Seeding '0' (NOT frozen)
 * makes the default state match a normal, operating deployment while keeping the
 * kill-switch instantly available from the Super Admin Settings page.
 *
 * WHY THROUGH THE MODEL (not DB::table()->insert): the read path caches the value
 * with Cache::rememberForever($key). A raw query-builder insert bypasses the
 * SettingObserver, so if getSettingValue() had already cached null for this key
 * (e.g. the settings page was opened before this migration ran) the seed would be
 * invisible until a manual cache flush. Writing via Setting::firstOrCreate fires
 * the observer, which forgets the cache key, so the '0' baseline takes effect
 * immediately. This makes the settings DB table the single source of truth that
 * the panel toggle also writes to (BdSelectController::toggleSalaryTransfer).
 *
 * Idempotent and non-destructive: firstOrCreate only inserts when the key is
 * absent, so an admin who has already toggled the switch is never overwritten.
 */
return new class extends Migration
{
    private const KEY = 'shipping_super_admin_stop_charge';

    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        // shipping_super_admin_stop_charge is a protected money key: the Setting
        // model blocks its write unless the sanctioned gate is open. This seed IS
        // a sanctioned writer, so open the gate for the firstOrCreate.
        \App\Helpers\Common::withMoneyKeyWrite(fn() => Setting::firstOrCreate(
            ['key' => self::KEY],
            ['value' => '0']
        ));
    }

    public function down(): void
    {
        // No-op on purpose: deleting the baseline row would leave the shipping
        // charge path frozen again (fail-closed), so a rollback must NOT remove
        // it. The row is harmless to keep.
    }
};
