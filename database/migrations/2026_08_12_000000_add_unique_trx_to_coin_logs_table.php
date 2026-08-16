<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('coin_logs')) {
            return;
        }

        if ($this->indexExists('coin_logs', 'coin_logs_trx_unique')) {
            return;
        }

        // MySQL allows multiple NULLs under a unique index, but the empty string
        // ('') is a real value and would collide. Normalise it to NULL first so
        // pending logs that never received a trx do not fail the ALTER.
        DB::table('coin_logs')->where('trx', '')->update(['trx' => null]);

        // Fail loud if non-NULL duplicates still exist: the ALTER would abort
        // mid-flight otherwise, leaving the table half-migrated. These rows must
        // be reconciled by hand before deploy (see coinlogs:check-trx-duplicates).
        $duplicates = DB::table('coin_logs')
            ->select('trx', DB::raw('COUNT(*) as c'))
            ->whereNotNull('trx')
            ->groupBy('trx')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            $summary = $duplicates
                ->map(fn ($row) => $row->trx . ' x' . $row->c)
                ->implode(', ');

            throw new RuntimeException(
                'Cannot add unique index on coin_logs.trx: '
                . $duplicates->count() . ' duplicate reference(s) exist [' . $summary . ']. '
                . 'Reconcile these rows before running this migration '
                . '(run: php artisan coinlogs:check-trx-duplicates).'
            );
        }

        Schema::table('coin_logs', function (Blueprint $table) {
            // A gateway transaction reference may be credited at most once.
            $table->unique('trx', 'coin_logs_trx_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('coin_logs')) {
            return;
        }

        if (!$this->indexExists('coin_logs', 'coin_logs_trx_unique')) {
            return;
        }

        Schema::table('coin_logs', function (Blueprint $table) {
            $table->dropUnique('coin_logs_trx_unique');
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