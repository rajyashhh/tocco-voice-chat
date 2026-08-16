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
        Schema::table('room_visitors', function (Blueprint $table) {
            $table->dropIndex('room_visitors_room_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_visitors', function (Blueprint $table) {
            $table->index('room_id', 'room_visitors_room_id_index');
        });
    }
};
