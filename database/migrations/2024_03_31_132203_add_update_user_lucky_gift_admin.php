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
        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->integer('total_win')->default(0);
            $table->integer('total_num_win')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->dropColumn('total_win');
            $table->dropColumn('total_num_win');
        });
    }
};
