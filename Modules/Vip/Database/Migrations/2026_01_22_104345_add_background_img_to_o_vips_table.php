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
            if (!Schema::hasColumn('o_vips', 'background_img')) {
                $table->string('background_img')->nullable()->after('img');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('o_vips', function (Blueprint $table) {
            if (Schema::hasColumn('o_vips', 'background_img')) {
                $table->dropColumn('background_img');
            }
        });
    }
};
