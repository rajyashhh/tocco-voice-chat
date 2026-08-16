<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('targets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('level')->nullable();
            $table->unsignedBigInteger('diamonds')->nullable();
            $table->unsignedBigInteger('minuts')->nullable();
            $table->unsignedBigInteger('hours')->nullable();
            $table->unsignedBigInteger('days')->nullable();
            $table->string('img')->nullable();
            $table->decimal('usd')->nullable();
            $table->decimal('coin')->nullable();
            $table->decimal('gold')->nullable();
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
        Schema::dropIfExists('targets');
    }
}
