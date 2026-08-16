<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalaryTrxsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('salary_trxs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->comment('0=user 1=agency')->nullable();
            $table->unsignedBigInteger('oid')->comment('object id');
            $table->double('amount')->nullable();
            $table->string('t_no')->nullable();
            $table->string('note')->nullable();
            $table->double('before_pay')->nullable();
            $table->double('after_pay')->nullable();
            $table->unsignedBigInteger('payer_id')->nullable();
            $table->unsignedTinyInteger('payer_type')->nullable();
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
        Schema::dropIfExists('salary_trxs');
    }
}
