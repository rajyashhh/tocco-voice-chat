<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
//        Schema::create('fixed_targets', function (Blueprint $table) {
//            $table->id();
//            $table->unsignedBigInteger('diamonds')->nullable();
//            $table->unsignedBigInteger('hours')->nullable();
//            $table->unsignedBigInteger('days')->nullable();
//            $table->integer('count_moment')->default(0);
//            $table->integer('count_real')->default(0);
//            $table->decimal('usd')->nullable();
//            $table->double ('agency_share')->nullable ()->default (0);
//            $table->string('img')->nullable();
//            $table->decimal('coin')->nullable();
//            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('fixed_targets');
    }
};
