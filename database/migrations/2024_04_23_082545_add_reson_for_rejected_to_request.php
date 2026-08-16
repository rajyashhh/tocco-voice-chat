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
        Schema::table('request_take_salaries', function (Blueprint $table) {
            $table->text ('reason_rejected')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_take_salaries', function (Blueprint $table) {
            $table->dropColumn('reason_rejected');
        });
    }
};
