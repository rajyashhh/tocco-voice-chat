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
        Schema::table('coin_game_users_archive', function (Blueprint $table) {
            $table->string('order_id')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coin_game_users_archive', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();

        });
    }
};
