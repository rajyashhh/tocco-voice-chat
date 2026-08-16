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
        Schema::table('change_level_histories', function (Blueprint $table) {
            $table->integer('new_total_sender_level')->nullable()->change();
            $table->integer('new_total_received_level')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_level_histories', function (Blueprint $table) {
            //
        });
    }
};
