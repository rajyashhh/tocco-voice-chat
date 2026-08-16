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
        Schema::table('user_sallaries', function (Blueprint $table) {
            $table->decimal('agency_sallary', 20, 4)->change();
            $table->decimal('sallary', 20, 4)->change();
            $table->decimal('cut_amount', 20, 4)->change();
            $table->decimal('db', 20, 4)->change();
            $table->decimal('app_profit', 20, 4)->change();
        });
    }

    public function down()
    {
        Schema::table('user_sallaries', function (Blueprint $table) {
            $table->double('agency_sallary', 20, 2)->change();
            $table->double('sallary', 20, 2)->change();
            $table->double('cut_amount', 20, 2)->change();
            $table->double('db', 20, 2)->change();
            $table->double('app_profit', 20, 2)->change();
        });
    }
};
