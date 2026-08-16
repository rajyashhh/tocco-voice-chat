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
        Schema::table('room_cup_targets', function (Blueprint $table) {
            $table->decimal('owner_profit', 45, 2)->default(0)->change();
            $table->decimal('admin_profit', 45, 2)->default(0)->change();
            $table->decimal('total_profit', 45, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_cup_targets', function (Blueprint $table) {
            //
        });
    }
};
