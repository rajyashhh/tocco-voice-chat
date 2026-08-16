<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Pre-flight check for the coin_logs.trx unique index migration.
 *
 * The ALTER that adds coin_logs_trx_unique aborts if two non-NULL rows share a
 * trx. NULLs are allowed to repeat under a MySQL unique index, but the empty
 * string ('') is a real value and WILL collide. Run this before deploying the
 * migration; if it reports duplicates they must be reconciled first.
 *
 * Exit codes: 0 = clean, 1 = duplicates found (do not migrate).
 */
class CheckCoinLogTrxDuplicates extends Command
{
    protected $signature = 'coinlogs:check-trx-duplicates';

    protected $description = 'Detect coin_logs.trx values that would break the unique index migration';

    public function handle(): int
    {
        $duplicates = DB::table('coin_logs')
            ->select('trx', DB::raw('COUNT(*) as c'), DB::raw('GROUP_CONCAT(id) as ids'))
            ->whereNotNull('trx')
            ->groupBy('trx')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $emptyCount = DB::table('coin_logs')->where('trx', '')->count();

        if ($duplicates->isEmpty() && $emptyCount === 0) {
            $this->info('OK: no duplicate or empty-string trx values. Safe to add the unique index.');
            return self::SUCCESS;
        }

        if ($duplicates->isNotEmpty()) {
            $this->error('Duplicate non-NULL trx values found (these will fail the ALTER):');
            $this->table(
                ['trx', 'count', 'coin_log ids'],
                $duplicates->map(fn ($row) => [$row->trx, $row->c, $row->ids])->all()
            );
        }

        if ($emptyCount > 0) {
            $this->error("Empty-string trx rows: {$emptyCount} (the '' value collides like a real duplicate).");
        }

        $this->warn('Reconcile the rows above before running the unique-index migration.');

        return self::FAILURE;
    }
}
