<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUnionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('img')->comment('صورة التأهيل')->nullable();
            $table->string('nickname')->comment('كنية')->nullable();
            $table->string('notice')->nullable();
            $table->text('contents')->nullable();
            $table->string('phone')->comment('رقم الاتصال')->nullable();
            $table->string('url')->comment('الصورة الرمزية');
            $table->unsignedTinyInteger('status')->default('1')->comment('1 عادي 2 معطل 3 حذف')->nullable();
            $table->unsignedInteger('users_id')->comment('إضافة مستخدم')->nullable();
            $table->timestamp('check_time')->comment('وقت المراجعة')->nullable();
            $table->unsignedInteger('check_uid')->comment('مستخدم التدقيق')->nullable();
            $table->unsignedTinyInteger('check_status')->comment('0 لم تتم مراجعته 1 تمت مراجعته 2 مرفوض')->nullable();
            $table->unsignedInteger('admin_id')->comment('معرف حساب المسؤول')->nullable();
            $table->float('share')->comment('قسّم إلى نسبة')->nullable();
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
        Schema::dropIfExists('unions');
    }
}
