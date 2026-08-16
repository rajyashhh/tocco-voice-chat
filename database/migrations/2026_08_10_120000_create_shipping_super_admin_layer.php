<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The coin balance for every admin-side actor (country manager, sub admin,
        // shipping super admin) lives on admin_users.di. The original
        // add_di_to_admin_users migration left the column commented out, yet the
        // country-manager charge path already reads/writes admin_users.di. Add it
        // here — guarded — so the column exists exactly once regardless of ordering.
        if (!Schema::hasColumn('admin_users', 'di')) {
            Schema::table('admin_users', function (Blueprint $table) {
                $table->bigInteger('di')->default(0)->nullable()->comment('coin balance (diamonds)');
            });
        }

        // Dedicated coin ledger for the shipping super admin layer. Every funding
        // (country manager -> shipping super admin) and every downward charge
        // (shipping super admin -> shipping agency) writes TWO rows here (an out
        // leg and an in leg) sharing one operation_uuid. The unique
        // (operation_uuid, type) index is the idempotency backstop: a retried
        // request can never double-apply a leg.
        Schema::create('shipping_admin_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('operation_uuid', 64);
            // shipping_fund_out | shipping_fund_in | shipping_charge_out | shipping_charge_in
            $table->string('type', 40);
            // fund | charge
            $table->string('operation', 20);

            // actor whose balance was debited
            $table->string('sender_type', 40);      // country_manager | shipping_super_admin
            $table->unsignedBigInteger('sender_id');

            // party whose balance was credited
            $table->string('receiver_type', 40);    // shipping_super_admin | shipping_agency
            $table->unsignedBigInteger('receiver_id');

            $table->bigInteger('coins');
            $table->bigInteger('before_amount')->default(0);
            $table->bigInteger('after_amount')->default(0);

            $table->unsignedBigInteger('country_id')->nullable();
            // admin_users.id of the authenticated actor that performed the move
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->unique(['operation_uuid', 'type'], 'shipping_admin_tx_uuid_type_unique');
            $table->index('sender_id');
            $table->index('receiver_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_admin_transactions');
        // di is left in place: it is shared with the country-manager path and
        // predates this layer, so dropping it here would break unrelated features.
    }
};
