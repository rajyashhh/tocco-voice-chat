<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('store_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index()->comment('0 is background')->nullable();
            $table->decimal('get_nums',20,2)->comment('إجمالي مبلغ التحويل')->nullable();
            $table->unsignedTinyInteger('get_type')->nullable();
            $table->decimal('now_nums',20,2)->comment('الرصيد الحالي')->nullable();
            $table->string('adduser')->comment('مسؤول الخلفية')->nullable();
            $table->string('symbol')->nullable();
            $table->tinyInteger('types')->comment('Currency Type 1=Diamond 2=Coin 3=Room Flow')->nullable();
            $table->integer('union_id')->comment('معرف النقابة')->nullable();
            $table->integer('family_id')->comment('معرف الاسرة')->nullable();
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
        Schema::dropIfExists('store_logs');
    }
}
