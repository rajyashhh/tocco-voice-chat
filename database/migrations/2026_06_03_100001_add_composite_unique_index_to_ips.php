<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Makes the updateOrCreate lookup inside TrackUserIpJob a single-row point
     * query by adding a composite UNIQUE(ip, uid). Duplicates are removed first
     * (no prior constraint existed), keeping the most recent row per (ip,uid).
     */
    public function up(): void
    {
        // 1) Dedup: keep the highest id per (ip, uid). Must precede the UNIQUE
        //    index or it fails with 1062. NULL ips/uids are left untouched
        //    (NULL != NULL, so they never collide under a UNIQUE index).
        DB::statement('
            DELETE i1 FROM ips i1
            INNER JOIN ips i2
                ON i1.ip = i2.ip
               AND i1.uid = i2.uid
               AND i1.id < i2.id
        ');

        // 2) Add the composite UNIQUE index (online-friendly on InnoDB).
        if (!$this->indexExists('ips', 'uq_ips_ip_uid')) {
            DB::statement('ALTER TABLE ips ADD UNIQUE INDEX uq_ips_ip_uid (ip, uid), ALGORITHM=INPLACE, LOCK=NONE');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('ips', 'uq_ips_ip_uid')) {
            Schema::table('ips', function ($table) {
                $table->dropUnique('uq_ips_ip_uid');
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
