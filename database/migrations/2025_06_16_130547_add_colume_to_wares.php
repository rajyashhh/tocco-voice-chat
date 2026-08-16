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
            $table->double('top')->default(0)->change();
            $table->double('left')->default(0)->change();
            $table->double('right')->default(0)->change();
            $table->double('bottom')->default(0)->change();

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
