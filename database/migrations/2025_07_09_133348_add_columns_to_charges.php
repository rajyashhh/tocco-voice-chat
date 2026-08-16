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
        Schema::table('charges', function (Blueprint $table) {
            $table->longText('reason_en')->nullable();
            $table->longText('reason_ar')->nullable();
            $table->string('invoice')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
           $table->dropColumn('reason_en');
           $table->dropColumn('reason_ar');
           $table->dropColumn('invoice');

        });
    }
};
