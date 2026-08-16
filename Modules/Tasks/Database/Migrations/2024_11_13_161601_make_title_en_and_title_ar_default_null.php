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
        //
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->default(null)->change();
            $table->string('title_en')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->string('title_ar')->nullable(false)->default('')->change();
            $table->string('title_en')->nullable(false)->default('')->change();
        });
    }
};
