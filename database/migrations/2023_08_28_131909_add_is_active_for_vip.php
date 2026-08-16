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
        Schema::table('wares', function (Blueprint $table) {
            $table->unsignedBigInteger('is_active_for_vip')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wares', function (Blueprint $table) {
            $table->dropColumn('is_active_for_vip');
        });
    }
};
