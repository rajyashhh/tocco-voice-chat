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
        Schema::table('achievement_valid_images', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable();
            $table->string('file')->nullable();

        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievement_valid_images', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropColumn('file');
        });
    }
};
