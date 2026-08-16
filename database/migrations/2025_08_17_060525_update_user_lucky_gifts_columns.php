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
            $table->bigInteger('number')->change();
            $table->bigInteger('total_num_win')->change();
            $table->bigInteger('total_win')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_lucky_gifts', function (Blueprint $table) {
            $table->integer('number')->change();
            $table->integer('total_num_win')->change();
            $table->integer('total_win')->change();
        });
    }
};
