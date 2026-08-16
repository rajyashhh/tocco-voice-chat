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
        Schema::table('all_games', function (Blueprint $table) {
            $table->string('custom_id')->nullable();
            $table->string('url')->nullable();
            $table->string('image')->nullable();
            $table->string('mini_url')->nullable();
            $table->string('is_enable')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('all_games', function (Blueprint $table) {
            $table->dropColumn('custom_id');
            $table->dropColumn('url');
            $table->dropColumn('image');
            $table->dropColumn('mini_url');
            $table->dropColumn('is_enable');
        });
    }
};
