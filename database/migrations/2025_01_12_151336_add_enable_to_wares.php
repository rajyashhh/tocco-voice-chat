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
        Schema::table('wares', function (Blueprint $table) {
            $table->unsignedInteger('enable')->comment('1 تمكين 2 تعطيل')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wares', function (Blueprint $table) {
            //
        });
    }
};
