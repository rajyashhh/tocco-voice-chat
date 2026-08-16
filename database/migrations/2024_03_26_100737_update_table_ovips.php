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
        Schema::table('o_vips', function (Blueprint $table) {
            $table->integer('sort')->after('id')->default(0);

        });
    }

    public function down(): void
    {
        Schema::table('o_vips', function (Blueprint $table) {
            $table->integer('sort')->after('id')->default(0);
        });
    }
};
