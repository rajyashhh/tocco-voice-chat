<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->decimal('agency_sallary', 20, 4)->change();
            $table->decimal('user_sallary', 20, 4)->change();
            $table->decimal('amount', 20, 4)->change();
        });
    }

    public function down()
    {
        Schema::table('bd_agency_host_sallaries', function (Blueprint $table) {
            $table->decimal('agency_sallary', 12, 2)->change();
            $table->decimal('user_sallary', 12, 2)->change();
            $table->decimal('amount', 12, 2)->change();
        });
    }
};
