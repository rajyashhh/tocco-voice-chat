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
            $table->string('hight_image')->nullable();
            $table->integer('in_room')->default(0);
            $table->string('hight')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('all_games', function (Blueprint $table) {
            $table->dropColumn('hight_image');
            $table->dropColumn('in_room');
            $table->dropColumn('hight');
        });
    }
};
