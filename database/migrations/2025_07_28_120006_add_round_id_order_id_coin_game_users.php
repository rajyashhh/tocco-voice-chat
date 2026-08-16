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
        Schema::table('coin_game_users', function (Blueprint $table) {
            $table->string('round_id')->after('game_id')->nullable();
            $table->string('order_id')->after('round_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coin_game_users', function (Blueprint $table) {
            $table->dropColumn('round_id');
            $table->dropColumn('order_id');
        });
    }
};
