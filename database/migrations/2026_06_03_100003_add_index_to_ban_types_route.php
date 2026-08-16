<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Index ban_types.route so the per-request action-ban lookup
     * (BanType::where('route', ...)) is a ref seek instead of a scan.
     */
    public function up(): void
    {
        if (!$this->indexExists('ban_types', 'idx_ban_types_route')) {
            DB::statement('ALTER TABLE ban_types ADD INDEX idx_ban_types_route (route), ALGORITHM=INPLACE, LOCK=NONE');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('ban_types', 'idx_ban_types_route')) {
            Schema::table('ban_types', function ($table) {
                $table->dropIndex('idx_ban_types_route');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) as count
             FROM information_schema.statistics
             WHERE table_schema = ?
               AND table_name = ?
               AND index_name = ?',
            [$databaseName, $table, $index]
        );

        return $result[0]->count > 0;
    }
};
