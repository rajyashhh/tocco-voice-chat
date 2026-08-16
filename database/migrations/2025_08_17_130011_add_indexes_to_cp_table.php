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
        Schema::table('cps', function (Blueprint $table) {
            $table->index('status', 'idx_status');
            $table->index('cp_relation_id', 'idx_relation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cps', function (Blueprint $table) {
            $table->index('status', 'idx_status');
            $table->index('cp_relation_id', 'idx_relation');
        });
    }
};
