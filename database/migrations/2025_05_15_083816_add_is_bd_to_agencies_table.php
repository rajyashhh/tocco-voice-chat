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
        Schema::table('agencies', function (Blueprint $table) {
            $table->bigInteger('bd_id')->default(0);     
        });
    }
    
    public function down()
    {
        Schema::table('agencies', function (Blueprint $table) {
            $table->dropColumn('bd_id');
        });
    }
};
