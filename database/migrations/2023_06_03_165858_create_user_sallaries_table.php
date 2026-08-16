<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserSallariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_sallaries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->default (0);
            $table->string  ('hours')->default ('0/0');
            $table->string ('days')->default ('0/0');
            $table->float('sallary',20,2)->default (0);
            $table->float('agency_sallary',20,2)->default (0);
            $table->float('cut_amount',20,2)->default (0);
            $table->integer('month')->default (0);
            $table->integer('year')->default (0);
            $table->boolean('is_paid')->default (0);
            $table->unsignedBigInteger('user_agency_id')->default (0);
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
        Schema::dropIfExists('user_sallaries');
    }
}
