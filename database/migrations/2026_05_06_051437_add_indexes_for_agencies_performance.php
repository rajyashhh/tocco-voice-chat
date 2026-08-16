<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These indexes fix the agencies/* endpoints performance issues reported in:
     * - GET /api/agencies/details/{id} (1.1s - 8.7s)
     * - GET /api/agencies/history/{id} (0.3s - 6.5s)
     * - GET /api/agencies/target-details/{id} (1.0s - 5.3s)
     *
     * Root cause: bd_agency_host_sallaries table (2,579 rows) has NO indexes except PRIMARY
     *
     * Expected Impact: Reduce query time from 5-8s to <500ms
     */
    public function up(): void
    {
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            // For queries filtering by agency_id
            if (!$this->indexExists('bd_agency_host_sallaries', 'idx_agency_id')) {
                $table->index('agency_id', 'idx_agency_id');
            }

            // For queries filtering by created_at (history, date ranges)
            if (!$this->indexExists('bd_agency_host_sallaries', 'idx_created_at')) {
                $table->index('created_at', 'idx_created_at');
            }

            // Composite index for queries with both agency_id AND created_at
            // This is the most powerful - covers queries like:
            // WHERE agency_id = ? AND created_at BETWEEN ? AND ?
            if (!$this->indexExists('bd_agency_host_sallaries', 'idx_agency_month')) {
                $table->index(['agency_id', 'created_at'], 'idx_agency_month');
            }

            // Index for user_id if used in joins/filtering
            if (!$this->indexExists('bd_agency_host_sallaries', 'idx_user_id')) {
                $table->index('user_id', 'idx_user_id');
            }
        });

        // Ensure agencies table has proper indexes (likely already exists, but check)
        Schema::table('agencies', function (Blueprint $table) {
            if (!$this->indexExists('agencies', 'idx_app_owner_id')) {
                $table->index('app_owner_id', 'idx_app_owner_id');
            }

            if (!$this->indexExists('agencies', 'idx_created_at')) {
                $table->index('created_at', 'idx_created_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            if ($this->indexExists('bd_agency_host_sallaries', 'idx_agency_id')) {
                $table->dropIndex('idx_agency_id');
            }
            if ($this->indexExists('bd_agency_host_sallaries', 'idx_created_at')) {
                $table->dropIndex('idx_created_at');
            }
            if ($this->indexExists('bd_agency_host_sallaries', 'idx_agency_month')) {
                $table->dropIndex('idx_agency_month');
            }
            if ($this->indexExists('bd_agency_host_sallaries', 'idx_user_id')) {
                $table->dropIndex('idx_user_id');
            }
        });

        Schema::table('agencies', function (Blueprint $table) {
            if ($this->indexExists('agencies', 'idx_app_owner_id')) {
                $table->dropIndex('idx_app_owner_id');
            }
            if ($this->indexExists('agencies', 'idx_created_at')) {
                $table->dropIndex('idx_created_at');
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $conn = Schema::getConnection();
        $dbSchemaManager = $conn->getDoctrineSchemaManager();
        $doctrineTable = $dbSchemaManager->introspectTable($table);

        return $doctrineTable->hasIndex($index);
    }
};
