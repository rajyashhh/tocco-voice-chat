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
            $table->integer('top')->default(0);
            $table->integer('left')->default(0);
            $table->integer('right')->default(0);
            $table->integer('bottom')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wares', function (Blueprint $table) {
            $table->dropColumn('top');
            $table->dropColumn('left');
            $table->dropColumn('right');
            $table->dropColumn('bottom');
        });
    }
};
