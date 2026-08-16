<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        /*
        Schema::table('monthly_diamond_receives', function (Blueprint $table) {
            $table->unique(['user_id', 'month', 'year'], 'idx_user_month_year');
        });*/
    }

    public function down(): void
    {
        Schema::table('monthly_diamond_receives', function (Blueprint $table) {
            $table->dropUnique('idx_user_month_year');
        });
    }
};
