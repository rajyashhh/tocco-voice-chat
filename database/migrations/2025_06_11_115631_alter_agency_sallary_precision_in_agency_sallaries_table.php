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
        Schema::table('agency_sallaries', function (Blueprint $table) {
            $table->decimal('sallary', 20, 4)->change();
            $table->decimal('cut_amount', 20, 4)->change();
        });
    }

    public function down()
    {
        Schema::table('agency_sallaries', function (Blueprint $table) {
            $table->double('sallary', 20, 2)->change();
            $table->double('cut_amount', 20, 2)->change();
        });
    }
};
