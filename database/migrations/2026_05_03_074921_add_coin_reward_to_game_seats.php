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
        Schema::table('game_seats', function (Blueprint $table) {
            $table->integer('coin_reward')->default(0)->after('end_rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_seats', function (Blueprint $table) {
            $table->dropColumn('coin_reward');
        });
    }
};
