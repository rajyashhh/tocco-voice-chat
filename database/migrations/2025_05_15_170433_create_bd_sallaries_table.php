<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBdSallariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bd_sallaries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('bd_id')->default (0);
            $table->unsignedBigInteger('agency_id')->default (0);
            $table->double('sallary', 15, 2)->default(0);
            $table->float('cut_amount')->default (0);
            $table->integer('month')->default (0);
            $table->integer('year')->default (0);
            $table->boolean('is_paid')->default (0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bd_sallaries');
    }
}
