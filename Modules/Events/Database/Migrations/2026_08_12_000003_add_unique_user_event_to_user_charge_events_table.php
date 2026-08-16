<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }

    public function up(): void
    {
        DB::statement('
            DELETE t1 FROM user_charge_events t1
            INNER JOIN user_charge_events t2
                ON t1.user_id = t2.user_id
                AND t1.charge_event_id = t2.charge_event_id
                AND t1.id > t2.id
        ');

        Schema::table('user_charge_events', function (Blueprint $table) {
            if (!$this->hasIndex('user_charge_events', 'user_charge_events_user_event_unique')) {
                $table->unique(['user_id', 'charge_event_id'], 'user_charge_events_user_event_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_charge_events', function (Blueprint $table) {
            if ($this->hasIndex('user_charge_events', 'user_charge_events_user_event_unique')) {
                $table->dropUnique('user_charge_events_user_event_unique');
            }
        });
    }
};