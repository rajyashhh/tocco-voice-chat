<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Align agencies.coins with the integer-balance spec.
 *
 * Problem
 * -------
 * agencies.coins is currently `double NOT NULL DEFAULT 0`. Coin balances are
 * integer quantities by spec (there is no sub-coin unit). DOUBLE carries
 * floating-point representation error that compounds across the increment/
 * decrement money paths and can produce balances like 999.9999999. A prior
 * migration (2025_05_13_090728) declared unsignedBigInteger via ->change() but
 * was never applied to some installs, so the column drifted back to DOUBLE.
 * This makes the fix idempotent and self-contained.
 *
 * All write paths credit/debit integer amounts (Model::increment/decrement and
 * `$agency->coins += $amount`). The only theoretical fraction source is
 * `$amount * shipping_coins` in ChargeAction/ChargeAction2, where shipping_coins
 * is an admin-set rate; if that rate were ever non-integer the product could be
 * fractional. Converting to an integer column removes that class of drift at the
 * storage layer.
 *
 * Rounding policy for any existing fractional values: FLOOR (truncate down).
 * Rationale: coins are spendable balance held BY the agency. ROUND could round
 * a fraction UP and mint coins the agency never paid for. FLOOR guarantees the
 * error can only ever be < 1 coin and always leans toward the platform, never
 * toward the agency — we never create value out of a rounding artifact. On the
 * inspected dataset (meow_qa_test) there are 0 fractional and 0 negative rows,
 * so in practice this touches nothing; the FLOOR is a safety net for client
 * installs whose DOUBLE column accumulated representation error.
 *
 * Target type: BIGINT UNSIGNED NOT NULL DEFAULT 0 (a coin balance is never
 * negative for an agency; every debit path already gates on sufficiency before
 * decrement).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('agencies', 'coins')) {
            return;
        }

        // Snapshot before conversion: how many rows carry a fraction, the range,
        // and the summed balance (checksum) so we can prove FLOOR lost nothing
        // it should not have.
        $before = DB::selectOne("
            SELECT
                COUNT(*)                                          AS total,
                COUNT(CASE WHEN coins != FLOOR(coins) THEN 1 END) AS fractional,
                COUNT(CASE WHEN coins < 0 THEN 1 END)             AS negatives,
                COALESCE(SUM(coins), 0)                           AS sum_coins,
                COALESCE(SUM(FLOOR(coins)), 0)                    AS sum_floor
            FROM agencies
        ");

        Log::info('agencies.coins pre-conversion snapshot', (array) $before);
        $this->info("🔍 agencies.coins: total={$before->total}, fractional={$before->fractional}, negatives={$before->negatives}");

        if ($before->negatives > 0) {
            // A negative agency balance is unreachable through the code paths and
            // cannot survive an UNSIGNED column. Fail loud rather than silently
            // clamp real debt to 0.
            Log::error('agencies.coins has negative values; aborting UNSIGNED conversion', (array) $before);
            throw new \RuntimeException(
                "agencies.coins holds {$before->negatives} negative row(s); resolve them before converting to BIGINT UNSIGNED."
            );
        }

        // Truncate any fractional balances DOWN before the type change so the
        // engine's implicit cast cannot round UP on us.
        if ($before->fractional > 0) {
            $this->warn("⚠️  Flooring {$before->fractional} fractional agencies.coins row(s) before conversion");
            DB::statement('UPDATE agencies SET coins = FLOOR(coins) WHERE coins != FLOOR(coins)');
        }

        Schema::table('agencies', function (Blueprint $table) {
            $table->unsignedBigInteger('coins')->default(0)->change();
        });

        $after = DB::selectOne('SELECT COUNT(*) AS total, COALESCE(SUM(coins), 0) AS sum_coins FROM agencies');

        // Row count must be identical; summed balance must equal the pre-computed
        // FLOOR sum (i.e. we only ever dropped sub-coin fractions).
        if ((int) $after->total !== (int) $before->total) {
            throw new \RuntimeException('agencies row count changed during coins conversion.');
        }
        if ((int) $after->sum_coins !== (int) $before->sum_floor) {
            Log::error('agencies.coins checksum mismatch after FLOOR conversion', [
                'expected_sum_floor' => $before->sum_floor,
                'actual_sum'         => $after->sum_coins,
            ]);
            throw new \RuntimeException('agencies.coins checksum mismatch: FLOOR sum does not match post-conversion sum.');
        }

        $this->info('✅ agencies.coins converted to BIGINT UNSIGNED; checksum verified.');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('agencies', 'coins')) {
            return;
        }

        // Reverse to the prior storage type. Values stay identical (integers fit
        // exactly in DOUBLE); only the representation reverts.
        Schema::table('agencies', function (Blueprint $table) {
            $table->double('coins')->default(0)->change();
        });

        $this->warn('⚠️  agencies.coins reverted to DOUBLE; floating-point drift risk returns.');
    }

    private function info(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
        Log::info($message);
    }

    private function warn(string $message): void
    {
        if (app()->runningInConsole()) {
            echo $message . PHP_EOL;
        }
        Log::warning($message);
    }
};
