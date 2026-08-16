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
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->decimal('input', 65, 0)->change();
        });
        Schema::table('home_carousel_displays', function (Blueprint $table) {
            $table->decimal('duration', 65, 0)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->bigInteger('input')->change();
        });
        Schema::table('home_carousel_displays', function (Blueprint $table) {
            $table->integer('duration')->change();
        });
    }
};
