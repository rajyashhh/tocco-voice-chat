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
        Schema::table('wallet_logs', function (Blueprint $table) {
            $table->renameColumn('target_id', 'related_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_logs', function (Blueprint $table) {
            $table->renameColumn('related_id', 'target_id');
        });
    }
};
