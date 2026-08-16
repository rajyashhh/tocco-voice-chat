<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('numid')->index()->comment('غرفة');
            $table->integer('uid')->index()->comment('صاحب الغرفة');
            $table->string('room_status')->default('1')->comment('حالة الغرفة 1 عادية 2 مقفلة 3 محظورة 4 مغلقة')->nullable();
            $table->string('room_name')->comment('اسم الغرفة');
            $table->string('room_cover')->comment('غطاء الغرفة صورة')->nullable();
            $table->string('room_intro')->default('مرحبا بكم في غرفتي')->comment('إعلان الغرفة')->nullable();
            $table->string('room_pass')->comment('كلمة المرور الغرفة')->nullable();
            $table->string('room_class')->default('5')->comment('القسم الرئيسي يتم اختيارة من جدول الكاتيجوري')->nullable();
            $table->string('room_type')->index()->default('16')->comment('فئة الغرفة الفرعية يتم اختيارة من جدول الكاتيجوري')->nullable();
            $table->string('room_welcome')->default('مرحبا بكم في غرفتي ~ أتمنى أن تستمتع ~')->comment('تحية الغرفة')->nullable();
            $table->text('room_admin')->comment('مدير الغرفة')->nullable();
            $table->text('room_visitor')->comment('شاغلو الغرفة الحاليون ، قم بإزالة المالك')->nullable();
            $table->text('room_speak')->comment('قائمة حظر الغرف')->nullable();
            $table->text('room_sound')->comment('قائمة كتم الغرفة')->nullable();
            $table->text('room_black')->comment('قائمة الأشخاص الذين تم طردهم من الغرفة')->nullable();
            $table->unsignedTinyInteger('week_star')->default('2')->comment('1 لا 2 نعم')->nullable();
            $table->unsignedInteger('ranking')->comment('ترتيب عكسي')->nullable();
            $table->unsignedTinyInteger('is_popular')->default('2')->comment('هل هي شعبية 1 2 ليست كذلك')->nullable();
            $table->unsignedTinyInteger('secret_chat')->default('2')->comment('ما إذا كانت الدردشة السرية موصى بها 1 نعم 2 لا')->nullable();
            $table->unsignedTinyInteger('is_top')->default('2')->comment('ما إذا كان التمسك بالقمة 1 هو 2 ليس كذلك')->nullable();
            $table->unsignedTinyInteger('sort')->nullable();
            $table->unsignedInteger('room_background')->comment('معرف صورة خلفية الغرفة')->nullable();
            $table->string('microphone')->default('0,0,0,0,0,0,0,0,0,0')->comment('معلومات الميكروفون فارغة 0 ، -1 يقفل الميكروفون ، والآخرون مستخدمون')->nullable();
            $table->unsignedTinyInteger('super_uid')->default('2')->comment('ما إذا كان يمكن لصاحب الغرفة تعيين نسبة المشاركة')->nullable();
            $table->unsignedTinyInteger('is_afk')->comment('0 اترك 1 يلعب')->nullable();
            $table->unsignedInteger('hot')->comment('0 لا 1 نعم')->nullable();
            $table->text('room_judge')->comment('قضاة الغرفة')->nullable();
            $table->string('is_prohibit_sound')->default('0,0,0,0,0,0,0,0,0')->comment('سواء كان تعطيل الصوت امكانية تعطيل صوت الميكروفون 0 ليس ممنوع 1')->nullable();
            $table->string('openid')->comment('غير مفعل')->nullable();
            $table->string('commission_proportion')->comment('غير مفعل')->nullable();
            $table->string('fresh_time')->comment('وقت تحديث الغرفة ، غير ممكّن')->nullable();
            $table->unsignedTinyInteger('start_hour')->nullable();
            $table->unsignedTinyInteger('end_hour')->nullable();
            $table->unsignedTinyInteger('is_recommended')->default('2')->comment('سواء كان موصى به 1 نعم 2 لا')->nullable();
            $table->unsignedTinyInteger('play_num')->comment('مفتاح اللعبة الرقمي 1 على 0 إيقاف')->nullable();
            $table->unsignedTinyInteger('free_mic')->comment('بت مجاني للميكروفون 1 عند 0 إيقاف')->nullable();
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
        Schema::dropIfExists('rooms');
    }
}
