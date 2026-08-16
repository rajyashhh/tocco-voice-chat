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
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->dateTime('from_date')->change();
            $table->dateTime('to_date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_coin_logs', function (Blueprint $table) {
            $table->date('from_date')->change();
            $table->date('to_date')->change();
        });
    }
};
