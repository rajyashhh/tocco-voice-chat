<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links the ACTIVE withdrawal path (user_withdrawals, written by
 * WalletHelper::createWithdrawal) to the admin-defined withdraw methods
 * (payment_withdraw_types). Before this, the active path stored a free-form
 * `amount` + `meta` with no notion of which method the user chose — the old
 * PaymentWithdrawType system was orphaned (all its user APIs commented out).
 *
 * Nullable + ON DELETE SET NULL: existing rows (pre-unification) stay valid,
 * and deleting a method later doesn't cascade-delete historical payout records.
 * The chosen field values themselves live in user_withdrawals.meta.fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_withdrawals')) {
            return;
        }
        if (Schema::hasColumn('user_withdrawals', 'payment_withdraw_type_id')) {
            return;
        }

        Schema::table('user_withdrawals', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_withdraw_type_id')->nullable()->after('amount');
            if (Schema::hasTable('payment_withdraw_types')) {
                $table->foreign('payment_withdraw_type_id')
                    ->references('id')->on('payment_withdraw_types')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_withdrawals')
            || !Schema::hasColumn('user_withdrawals', 'payment_withdraw_type_id')) {
            return;
        }

        $hasForeign = collect(Schema::getForeignKeys('user_withdrawals'))
            ->contains(fn ($fk) => in_array('payment_withdraw_type_id', $fk['columns'], true));

        Schema::table('user_withdrawals', function (Blueprint $table) use ($hasForeign) {
            if ($hasForeign) {
                $table->dropForeign(['payment_withdraw_type_id']);
            }
            $table->dropColumn('payment_withdraw_type_id');
        });
    }
};