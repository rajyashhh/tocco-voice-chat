<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_coin_payments')) {
            return;
        }

        if ($this->indexExists('user_coin_payments', 'user_coin_payments_reference_id_unique')) {
            return;
        }

        // A gateway reference may be credited at most once. MySQL allows multiple
        // NULLs under a unique index, so historical rows that never received a
        // reference_id are unaffected. Empty strings ('') are treated as real
        // values and would collide, so normalise them to NULL first.
        DB::table('user_coin_payments')->where('reference_id', '')->update(['reference_id' => null]);

        // Fail loud if non-NULL duplicates still exist: the ALTER would abort
        // mid-flight otherwise. These must be reconciled by hand before deploy.
        $duplicates = DB::table('user_coin_payments')
            ->select('reference_id')
            ->whereNotNull('reference_id')
            ->groupBy('reference_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('reference_id')
            ->all();

        if (!empty($duplicates)) {
            throw new RuntimeException(
                'Cannot add unique index on user_coin_payments.reference_id: duplicate references exist ['
                . implode(', ', array_map('strval', $duplicates))
                . ']. Reconcile these rows before running this migration.'
            );
        }

        Schema::table('user_coin_payments', function (Blueprint $table) {
            $table->unique('reference_id', 'user_coin_payments_reference_id_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_coin_payments')) {
            return;
        }

        if (!$this->indexExists('user_coin_payments', 'user_coin_payments_reference_id_unique')) {
            return;
        }

        Schema::table('user_coin_payments', function (Blueprint $table) {
            $table->dropUnique('user_coin_payments_reference_id_unique');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return !empty(DB::select(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
            [$index]
        ));
    }
};
