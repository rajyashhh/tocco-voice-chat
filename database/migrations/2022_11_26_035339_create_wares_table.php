<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWaresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wares', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('get_type')->default('1')->comment('Obtaining method 1 vip level automatic acquisition 2 activity 3 treasure box 4 purchase 5 background modification 6 limited time purchase 7 treasure box point exchange 8 cp level unlock 104 not for sale')->nullable();
            $table->unsignedTinyInteger('type')->comment('1 الأحجار الكريمة 3 بطاقة التمرير 4 إطار الصورة الرمزية 5 إطار الفقاعة 6 دخول المؤثرات الخاصة 7 فتحة الميكروفون 8 شارة');
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('price')->comment('سعر')->nullable();
            $table->unsignedInteger('score')->comment('النقاط المطلوبة')->nullable();
            $table->unsignedTinyInteger('level')->comment('المستوى المطلوب لكبار الشخصيات')->nullable();
            $table->string('show_img')->nullable ();
            $table->string('img1')->nullable();
            $table->string('img2')->nullable();
            $table->string('img3')->nullable();
            $table->string('color')->nullable();
            $table->unsignedTinyInteger('expire')->default('1')->comment('الوقت الصالح 0 دائم يتم حساب الآخرين بالأيام')->nullable();
            $table->unsignedInteger('enable')->comment('1 تمكين 2 تعطيل')->nullable();
            $table->unsignedTinyInteger('sort')->nullable();
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
        Schema::dropIfExists('wares');
    }
}
