<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!$this->indexExists()) {
            Schema::table('coin_logs', function (Blueprint $table) {
                $table->index('created_at', 'idx_coin_logs_created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists()) {
            Schema::table('coin_logs', function (Blueprint $table) {
                $table->dropIndex('idx_coin_logs_created_at');
            });
        }
    }

    private function indexExists(): bool
    {
        return collect(DB::select(
            "SHOW INDEX FROM coin_logs WHERE Key_name = 'idx_coin_logs_created_at'"
        ))->isNotEmpty();
    }
};