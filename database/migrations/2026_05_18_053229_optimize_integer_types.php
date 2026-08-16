<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Optimization: Use appropriate integer sizes (TINYINT/SMALLINT instead of INT).
 *
 * Benefits:
 * - Storage: 2 bytes saved per row (INT 4 bytes → SMALLINT 2 bytes)
 * - Memory: Better cache efficiency
 * - Query performance: Faster comparisons on smaller integers
 *
 * Integer Ranges:
 * - TINYINT UNSIGNED: 0 to 255 (1 byte)
 * - TINYINT: -128 to 127 (1 byte)
 * - SMALLINT UNSIGNED: 0 to 65,535 (2 bytes) ← Used for sort columns
 * - SMALLINT: -32,768 to 32,767 (2 bytes)
 * - INT UNSIGNED: 0 to 4,294,967,295 (4 bytes)
 *
 * Note: gifts.sort and silvers.sort use SMALLINT UNSIGNED because real data goes up to 292
 *
 * Estimated Savings: ~400 MB (less than originally estimated due to SMALLINT vs TINYINT)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
        $this->info('🔍 Validating integer ranges before conversion...');

        $validation = $this->validateIntegerRanges();

        if (!$validation['safe']) {
            $errorMessage = "❌ Data validation failed! Cannot proceed with migration.\n\n";
            $errorMessage .= "Violations found:\n";
            foreach ($validation['violations'] as $violation) {
                $errorMessage .= "  - {$violation}\n";
            }

            Log::error('Integer type optimization migration failed validation', [
                'violations' => $validation['violations']
            ]);

            throw new \Exception($errorMessage);
        }

        $this->info('✅ Data validation passed. All values fit in proposed types.');

        // ==========================================
        // Convert sort columns to TINYINT UNSIGNED
        // ==========================================

        $this->info('🔄 Optimizing sort columns...');

        DB::statement("ALTER TABLE silvers MODIFY COLUMN sort SMALLINT UNSIGNED NULL");
        DB::statement("ALTER TABLE gifts MODIFY COLUMN sort SMALLINT UNSIGNED NULL");

        // ==========================================
        // Convert status columns to TINYINT UNSIGNED
        // ==========================================

        $this->info('🔄 Optimizing status columns...');

        DB::statement("ALTER TABLE countries MODIFY COLUMN status TINYINT UNSIGNED DEFAULT 0 NULL");

        // ==========================================
        // Convert type columns to TINYINT UNSIGNED
        // ==========================================

        $this->info('🔄 Optimizing type columns...');

        DB::statement("ALTER TABLE store_logs MODIFY COLUMN types TINYINT UNSIGNED NULL");

        $this->info('✅ Integer type optimization completed successfully!');
        */
    }

    /**
     * Validate integer ranges
     */
    private function validateIntegerRanges(): array
    {
        /*
        $violations = [];
        $safe = true;

        // Check silvers.sort (should be 0-255)
        $silverSort = DB::selectOne("
            SELECT MIN(sort) as min, MAX(sort) as max FROM silvers WHERE sort IS NOT NULL
        ");

        if (($silverSort->min ?? 0) < 0 || ($silverSort->max ?? 0) > 65535) {
            $violations[] = "silvers.sort range [{$silverSort->min}, {$silverSort->max}] exceeds SMALLINT UNSIGNED (0-65535)";
            $safe = false;
        }

        // Check gifts.sort (should be 0-65535 for SMALLINT UNSIGNED)
        $giftSort = DB::selectOne("
            SELECT MIN(sort) as min, MAX(sort) as max FROM gifts WHERE sort IS NOT NULL
        ");

        if (($giftSort->min ?? 0) < 0 || ($giftSort->max ?? 0) > 65535) {
            $violations[] = "gifts.sort range [{$giftSort->min}, {$giftSort->max}] exceeds SMALLINT UNSIGNED (0-65535)";
            $safe = false;
        }

        // Check countries.status (should be 0-255)
        $countryStatus = DB::selectOne("
            SELECT MIN(status) as min, MAX(status) as max FROM countries WHERE status IS NOT NULL
        ");

        if (($countryStatus->min ?? 0) < 0 || ($countryStatus->max ?? 0) > 255) {
            $violations[] = "countries.status range [{$countryStatus->min}, {$countryStatus->max}] exceeds TINYINT UNSIGNED (0-255)";
            $safe = false;
        }

        // Check store_logs.types (should be 0-255)
        $storeTypes = DB::selectOne("
            SELECT MIN(types) as min, MAX(types) as max FROM store_logs WHERE types IS NOT NULL
        ");

        if (($storeTypes->min ?? 0) < 0 || ($storeTypes->max ?? 0) > 255) {
            $violations[] = "store_logs.types range [{$storeTypes->min}, {$storeTypes->max}] exceeds TINYINT UNSIGNED (0-255)";
            $safe = false;
        }

        return [
            'safe' => $safe,
            'violations' => $violations
        ];*/
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->warn('⚠️  Rolling back integer type optimizations to INT');

        // Revert silvers
        DB::statement("ALTER TABLE silvers MODIFY COLUMN sort INT NULL");

        // Revert gifts
        DB::statement("ALTER TABLE gifts MODIFY COLUMN sort INT NULL");

        // Revert countries
        DB::statement("ALTER TABLE countries MODIFY COLUMN status INT DEFAULT 0 NULL");

        // Revert store_logs
        DB::statement("ALTER TABLE store_logs MODIFY COLUMN types INT NULL");

        $this->info('Rollback completed.');
    }

    /**
     * Helper methods
     */
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
            echo '⚠️  ' . $message . PHP_EOL;
        }
        Log::warning($message);
    }
};
