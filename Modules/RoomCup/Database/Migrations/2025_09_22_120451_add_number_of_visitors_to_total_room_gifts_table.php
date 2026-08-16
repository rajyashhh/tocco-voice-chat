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
        Schema::table('total_room_gifts', function (Blueprint $table) {
            $table->unsignedBigInteger('number_of_visitors')->default(0)->after('current_total');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('total_room_gifts', function (Blueprint $table) {
            $table->dropColumn('number_of_visitors');
        });
    }
};
