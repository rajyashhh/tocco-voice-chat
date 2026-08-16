<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->comment('اسم الهدية')->nullable();
            $table->string('e_name')->comment('الاسم الانجليزي')->nullable();
            $table->unsignedTinyInteger('type')->default('1')->comment('1 هدية عادية 2 هدية ساخنة')->nullable();
            $table->unsignedTinyInteger('vip_level')->default('0000')->comment('المستوى المطلوب لكبار الشخصيات')->nullable();
            $table->unsignedInteger('hot')->nullable();
            $table->unsignedTinyInteger('is_play')->comment('0 لا يوجد بث 1 خدمة كاملة البث')->nullable();
            $table->integer('price')->comment('السعر')->nullable();
            $table->string('img')->nullable();
            $table->string('show_img')->nullable();
            $table->string('show_img2')->nullable();
            $table->unsignedTinyInteger('sort')->default('1')->nullable();
            $table->unsignedTinyInteger('enable')->default('1')->comment('1 ممكنة 2 غير ممكنة')->nullable();
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
        Schema::dropIfExists('gifts');
    }
}
