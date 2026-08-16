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
        Schema::table('game_provider_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('game_provider_settings', 'channel')) {
                $table->string('channel')->nullable();
            }
            if (!Schema::hasColumn('game_provider_settings', 'app_id')) {
                $table->string('app_id')->nullable();
            }
            if (!Schema::hasColumn('game_provider_settings', 'gsp')) {
                $table->string('gsp')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_provider_settings', function (Blueprint $table) {
            $table->dropColumn(['channel', 'app_id', 'gsp']);
        });
    }
};
