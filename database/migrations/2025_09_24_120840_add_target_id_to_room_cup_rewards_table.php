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
        Schema::table('room_cup_rewards', function (Blueprint $table) {
            $table->unsignedBigInteger('target_id')->nullable()
                  ->comment('ID of the target ');
        });
    }

    public function down(): void
    {
        Schema::table('room_cup_rewards', function (Blueprint $table) {
            $table->dropColumn('target_id');
        });
    }
};
