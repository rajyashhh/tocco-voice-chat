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
        Schema::table('home_carousel_displays', function (Blueprint $table) {
            \DB::statement("
                ALTER TABLE `home_carousel_displays`
                MODIFY `display_type`
                ENUM('discover', 'home_top', 'home_middle', 'live', 'room', 'country')
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
            ");
        });
    }

    public function down(): void
    {
        Schema::table('home_carousel_displays', function (Blueprint $table) {
            \DB::statement("
                ALTER TABLE `home_carousel_displays`
                MODIFY `display_type`
                ENUM('discover', 'home_top', 'home_middle', 'live')
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
            ");
        });
    }
    
};

