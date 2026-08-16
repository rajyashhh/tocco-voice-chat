<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Option-B naming unification — collapse the three-way / two-way spelling forks
 * for the Country (was super-admin/superadmin/super_admin) and Region (was
 * area-manager/area_manager) positions into a single canonical token per layer.
 *
 * WHY per-column predicates (not one global REPLACE): the SAME role was stored
 * under DIFFERENT spellings depending on the column's layer —
 *   admin_users.type  : 'superadmin'  (one word) | 'area-manager' (hyphen)
 *   admin_roles.slug   : 'super-admin' (hyphen)   | 'area-manager' (hyphen)
 *   milestones.slug    : 'super-admin' (hyphen)   | 'area-manager' (hyphen)
 *   charges.*_type     : 'super_admin' (underscore)| 'area_manager' (underscore)
 *   charge_invoices.type: 'super_admin'            | 'area_manager'
 *   admin_rewards.user_type: 'super_admin'         | 'area_manager'
 * A blanket replace would miss one fork and corrupt half the rows, so each
 * column is rewritten against the exact old value(s) it actually holds.
 *
 * SCOPE GUARDS:
 *  - 'agency' is NEVER touched — it is shared by host-agency AND shipping-agency
 *    ledgers; renaming it would corrupt host-agency money history.
 *  - BD ('bd') and Shipping Super Admin ('shipping_super_admin') tokens are
 *    already clean/consistent and are intentionally left unchanged; only the
 *    misleading DISPLAY names change (handled in code/translations, not here).
 *  - shipping_admin_transactions.{sender_type,receiver_type} keep the literal
 *    'country_manager' — same class as 'agency'. It is a WRITE-ONLY ledger leg
 *    label (written by ShippingSuperAdminWalletService, never compared against
 *    admin_users.type by any reader), and its siblings in that same ledger are
 *    full descriptive names ('shipping_super_admin', 'shipping_agency'), so
 *    rewriting only this one leg to 'country' would make the ledger internally
 *    inconsistent for zero functional gain.
 *
 * IDEMPOTENT: every UPDATE keys off an OLD value that no longer exists after the
 * first run, so re-running is a no-op. Safe for fresh installs (0 rows) and for
 * the sale-copy migration. Fully reversible in down().
 */
return new class extends Migration
{
    /**
     * Canonical target tokens.
     */
    private const COUNTRY      = 'country';
    private const SUB_COUNTRY  = 'sub_country';
    private const REGION       = 'region';
    private const SUB_REGION   = 'sub_region';

    public function up(): void
    {
        DB::transaction(function () {
            // ---- admin_roles.slug (role identity) --------------------------
            $this->rename('admin_roles', 'slug', ['super-admin'], self::COUNTRY);
            $this->rename('admin_roles', 'slug', ['area-manager'], self::REGION);

            // ---- admin_users.type (STI discriminator; global scopes read it)-
            // Country manager was stored as the one-word 'superadmin'.
            $this->rename('admin_users', 'type', ['superadmin'], self::COUNTRY);
            $this->rename('admin_users', 'type', ['sub_super_admin'], self::SUB_COUNTRY);
            // Region manager was stored as the hyphenated 'area-manager'.
            $this->rename('admin_users', 'type', ['area-manager'], self::REGION);
            $this->rename('admin_users', 'type', ['sub_area_manager'], self::SUB_REGION);

            // ---- milestones.slug (reward coupling; matched in MilestoneJob) -
            if (Schema::hasTable('milestones')) {
                $this->rename('milestones', 'slug', ['super-admin'], self::COUNTRY);
                $this->rename('milestones', 'slug', ['area-manager'], self::REGION);
            }

            // ---- charges.* (money ledger; underscore tokens from UserTypeEnum)
            foreach (['charger_type', 'user_type', 'user_charger_type'] as $col) {
                $this->rename('charges', $col, ['super_admin'], self::COUNTRY);
                $this->rename('charges', $col, ['sub_super_admin'], self::SUB_COUNTRY);
                $this->rename('charges', $col, ['area_manager'], self::REGION);
                $this->rename('charges', $col, ['sub_area_manager'], self::SUB_REGION);
            }

            // ---- charge_invoices.type (holds super_admin / area_manager) ----
            if (Schema::hasTable('charge_invoices')) {
                $this->rename('charge_invoices', 'type', ['super_admin'], self::COUNTRY);
                $this->rename('charge_invoices', 'type', ['area_manager'], self::REGION);
            }

            // ---- admin_rewards.user_type (dedicate-reward target; closed loop:
            // written by the Dedicate*RewardAction forms, read back by the reward
            // history controllers). Underscore tokens, same as the charges layer.
            if (Schema::hasTable('admin_rewards')) {
                $this->rename('admin_rewards', 'user_type', ['super_admin'], self::COUNTRY);
                $this->rename('admin_rewards', 'user_type', ['area_manager'], self::REGION);
            }

            // shipping_admin_transactions.{sender_type,receiver_type} are left as
            // 'country_manager' on purpose — see the class docblock (write-only
            // ledger leg label, never compared to admin_users.type).
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            // admin_roles.slug
            $this->rename('admin_roles', 'slug', [self::COUNTRY], 'super-admin');
            $this->rename('admin_roles', 'slug', [self::REGION], 'area-manager');

            // admin_users.type
            $this->rename('admin_users', 'type', [self::COUNTRY], 'superadmin');
            $this->rename('admin_users', 'type', [self::SUB_COUNTRY], 'sub_super_admin');
            $this->rename('admin_users', 'type', [self::REGION], 'area-manager');
            $this->rename('admin_users', 'type', [self::SUB_REGION], 'sub_area_manager');

            // milestones.slug
            if (Schema::hasTable('milestones')) {
                $this->rename('milestones', 'slug', [self::COUNTRY], 'super-admin');
                $this->rename('milestones', 'slug', [self::REGION], 'area-manager');
            }

            // charges.*
            foreach (['charger_type', 'user_type', 'user_charger_type'] as $col) {
                $this->rename('charges', $col, [self::COUNTRY], 'super_admin');
                $this->rename('charges', $col, [self::SUB_COUNTRY], 'sub_super_admin');
                $this->rename('charges', $col, [self::REGION], 'area_manager');
                $this->rename('charges', $col, [self::SUB_REGION], 'sub_area_manager');
            }

            // charge_invoices.type
            if (Schema::hasTable('charge_invoices')) {
                $this->rename('charge_invoices', 'type', [self::COUNTRY], 'super_admin');
                $this->rename('charge_invoices', 'type', [self::REGION], 'area_manager');
            }

            // admin_rewards.user_type
            if (Schema::hasTable('admin_rewards')) {
                $this->rename('admin_rewards', 'user_type', [self::COUNTRY], 'super_admin');
                $this->rename('admin_rewards', 'user_type', [self::REGION], 'area_manager');
            }
        });
    }

    /**
     * Rewrite $column from any of $from values to $to, only when a row actually
     * matches. whereIn keeps it a no-op on already-migrated / empty tables.
     */
    private function rename(string $table, string $column, array $from, string $to): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->whereIn($column, $from)
            ->update([$column => $to]);
    }
};
