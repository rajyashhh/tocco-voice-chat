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
        Schema::table('room_boom_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('room_boom_levels', 'background_image')) {
                $table->string('background_image')->nullable();
            }
            if (!Schema::hasColumn('room_boom_levels', 'image_type_background')) {
                $table->string('image_type_background')->nullable();
            }
            if (!Schema::hasColumn('room_boom_levels', 'boom_image')) {
                $table->string('boom_image')->nullable();
            }
            if (!Schema::hasColumn('room_boom_levels', 'image_type_boom')) {
                $table->string('image_type_boom')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_boom_levels', function (Blueprint $table) {
            $table->dropColumn('background_image');
            $table->dropColumn('image_type_background');
            $table->dropColumn('boom_image');
            $table->dropColumn('image_type_boom');
        });
    }
};
