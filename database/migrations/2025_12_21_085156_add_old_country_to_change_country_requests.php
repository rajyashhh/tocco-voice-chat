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
        if (!Schema::hasColumn('change_country_requests', 'old_country')) {
            Schema::table('change_country_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('old_country')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_country_requests', function (Blueprint $table) {
            $table->dropColumn('old_country');
        });
    }
};
