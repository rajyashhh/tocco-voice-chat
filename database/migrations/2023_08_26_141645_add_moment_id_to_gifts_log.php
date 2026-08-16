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
        Schema::table('gift_logs', function (Blueprint $table) {
            // $table->unsignedInteger('moent_id')->nullable();
            $table->foreignId('moent_id')->nullable()->constrained('moment')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gift_logs', function (Blueprint $table) {
            $table->dropColumn('moent_id');

        });
    }
};
