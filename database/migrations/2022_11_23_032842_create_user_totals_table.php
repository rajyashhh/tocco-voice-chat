<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_totals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger ('user_id');
            $table->unsignedBigInteger ('room')->default (0)->comment ('اجمالي المبلغ الجاري في الغرفة');
            $table->unsignedBigInteger ('send')->default (0)->comment ('اجمالي المبلغ المرسل (الماس)');
            $table->unsignedBigInteger ('gain')->default (0)->comment ('المبلغ الإجمالي المستلم (الماس)');
            $table->unsignedTinyInteger ('vip_level')->default (0)->comment ('المستوى vip');
            $table->unsignedTinyInteger ('cp_level')->default (0)->comment ('المستوى cp');
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
        Schema::dropIfExists('user_totals');
    }
}
