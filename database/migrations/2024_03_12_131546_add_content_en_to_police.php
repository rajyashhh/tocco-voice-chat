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
        Schema::table('police', function (Blueprint $table) {
            $table->text('title_en')->nullable();
            $table->text('body_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('police', function (Blueprint $table) {
            $table->dropColumn('title_en');
            $table->dropColumn('body_en');
        });
    }
};
