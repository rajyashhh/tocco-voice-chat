<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fair_luck_wallet_histories', function (Blueprint $table) {
            // Composite index for the query: WHERE wallet_type = ? ORDER BY created_at DESC LIMIT 100
            // This prevents a full filesort on large tables
            $table->index(['wallet_type', 'created_at'], 'flwh_wallet_type_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('fair_luck_wallet_histories', function (Blueprint $table) {
            $table->dropIndex('flwh_wallet_type_created_at_index');
        });
    }
};