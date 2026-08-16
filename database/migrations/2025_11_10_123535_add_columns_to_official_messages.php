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
        Schema::table('official_messages', function (Blueprint $table) {
            $table->string('type_feature')->nullable();
            $table->longText('multi_feature')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('official_messages', function (Blueprint $table) {
            $table->dropColumn('type_feature');
            $table->dropColumn('multi_feature');
        });
    }
};
