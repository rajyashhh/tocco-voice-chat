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
//        Schema::table('agencies', function (Blueprint $table) {
//            $table->double("monthly_target")->default(1000000);
//        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('agencies', function (Blueprint $table) {
//            $table->dropColumn("monthly_target")->default(1000000);
//        });
    }
};
