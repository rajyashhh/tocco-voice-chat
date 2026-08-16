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
        if (!Schema::hasColumn('room_boom_levels', 'image_type')) {
            Schema::table('room_boom_levels', function (Blueprint $table) {
                $table->string('image_type')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_boom_levels', function (Blueprint $table) {
            $table->dropColumn('image_type');
        });
    }
};
