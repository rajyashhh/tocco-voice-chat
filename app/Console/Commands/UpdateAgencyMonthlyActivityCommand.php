<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

/**
 * Recompute agencies.monthly_activity = SUM(gift_logs.giftPrice) credited to
 * each agency (gift_logs.agency_id) for the CURRENT calendar month.
 *
 * This is the cheap, denormalized signal the agency listing orders by. Running
 * it on a schedule (hourly + at month start) keeps the listing endpoint a plain
 * indexed ORDER BY instead of a per-request correlated subquery over gift_logs.
 *
 * A single set-based UPDATE handles everything in one statement:
 *   - agencies with current-month gifts get the fresh sum,
 *   - agencies with none (including last month's leaders) collapse to 0,
 * which is exactly the month-start rollover behaviour we want.
 *
 * The (agency_id, created_at) index on gift_logs (idx_gift_logs_agency_id_created_at)
 * keeps the inner aggregation a range scan rather than a full table scan.
 */
class UpdateAgencyMonthlyActivityCommand extends Command
{
    protected $signature = 'agency:update-monthly-activity';
    protected $description = 'Recompute denormalized agencies.monthly_activity for the current calendar month';

    public function handle(): int
    {
        $monthStart = now()->startOfMonth()->toDateTimeString();
        $nextMonthStart = now()->startOfMonth()->addMonth()->toDateTimeString();

        // Single atomic, set-based UPDATE. The correlated subquery is bounded by
        // [monthStart, nextMonthStart) so it uses the (agency_id, created_at)
        // composite index, and COALESCE(...,0) resets agencies with no gifts
        // this month (the natural month rollover).
        $affected = DB::update(
            'UPDATE agencies SET monthly_activity = (
                SELECT COALESCE(SUM(gift_logs.giftPrice), 0)
                FROM gift_logs
                WHERE gift_logs.agency_id = agencies.id
                  AND gift_logs.created_at >= ?
                  AND gift_logs.created_at < ?
            )',
            [$monthStart, $nextMonthStart]
        );

        $this->info("Updated monthly_activity for {$affected} agencies.");

        return self::SUCCESS;
    }
}
