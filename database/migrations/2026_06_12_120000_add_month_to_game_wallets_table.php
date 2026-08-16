<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_wallets', function (Blueprint $table) {
            // Stored generated column: stays correct for every writer (admin
            // top-up, payment, scheduler) with no code changes, and is indexable —
            // replaces the unindexed whereMonth(created_at) on the hot bet path.
            $table->string('month', 7)
                ->storedAs("DATE_FORMAT(created_at, '%Y-%m')")
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_wallets', function (Blueprint $table) {
            $table->dropIndex(['month']);
            $table->dropColumn('month');
        });
    }
};
