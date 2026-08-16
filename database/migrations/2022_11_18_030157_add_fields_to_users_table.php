<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string ('phone')->unique ()->nullable ();
            $table->string ('google_id')->unique ()->nullable ();
            $table->string ('facebook_id')->unique ()->nullable ();
            $table->double ('di')->default (0)->nullable ()->comment ('رصيد الماس');
            $table->double ('coins')->default (0)->nullable ()->comment ('رصيد كوين');
            $table->double ('room_coins')->default (0)->nullable ()->comment ('ايرادات الغرفة كوين');
            $table->double ('flowers')->default (0)->nullable ()->comment ('زهور');
            $table->double ('flowers_value')->default (0)->nullable ()->comment ('قيمة الزهور');
            $table->double ('gold')->default (0)->nullable ()->comment ('ذهب');
            $table->boolean('is_leader')->default (false)->nullable ()->comment ('');
            $table->boolean('is_sign')->default (false)->nullable ()->comment ('');
            $table->boolean('isOnline')->default (false)->nullable ()->comment ('');
            $table->boolean('status')->default (true)->nullable ()->comment ('1 عادي 0 ممنوع تسجيل الدخول');
            $table->boolean('is_points_first')->default (false)->nullable ()->comment ('هل النقطة 500 لأول مرة 1 = نعم');
            $table->dateTime ('locktime')->nullable ()->comment ('وقت التوقيف');
            $table->unsignedInteger('online_time')->nullable ()->comment ('');
            $table->unsignedInteger('dress_1')->nullable ()->comment ('الصورة الرمزية الإطار واللباس');
            $table->unsignedInteger('dress_2')->nullable ()->comment ('صندوق الدردشه');
            $table->unsignedInteger('dress_3')->nullable ()->comment ('تأثيرات الدخول');
            $table->unsignedInteger('dress_4')->nullable ()->comment ('هالة على المايك');
            $table->unsignedInteger('cp_card')->nullable ()->comment ('عدد حقول CP');
            $table->unsignedInteger('keys_num')->nullable ()->comment ('عدد المفاتيح');
            $table->string('nickname')->nullable ()->comment ('');
            $table->string('idno')->nullable ()->comment ('رقم الهوية');
            $table->string('mykeep')->nullable ()->comment ('غرفتي المفضلة');
            $table->string('system')->nullable ()->default ('normal')->comment ('');
            $table->string('channel')->nullable ()->default ('normal')->comment ('');
            $table->string('img_1')->nullable ()->comment ('');
            $table->string('img_2')->nullable ()->comment ('');
            $table->string('img_3')->nullable ()->comment ('');
            $table->unsignedInteger('points')->default (0)->nullable ()->comment ('غرفتي المفضلة');
            $table->string('login_ip')->nullable ()->comment ('');
            $table->string('device_token')->unique ()->nullable ()->comment ('');
            $table->unsignedInteger ('scale')->default (0)->nullable ()->comment ('معدل الدوران مقسم إلى نسبة وحدة (٪)');
            $table->unsignedTinyInteger('is_idcard')->default (0)->nullable ()->comment ('التاكد من الهوية 0 لم تؤكد 1 تم تاكيدها');

        });

        Schema::create ('profiles',function (Blueprint $table){
            $table->id ();
            $table->foreignId ('user_id')->constrained('users')->onDelete ('cascade');
            $table->string('avatar')->nullable ()->comment ('');
            $table->unsignedTinyInteger('gender')->default (0)->nullable ()->comment ('');
            $table->dateTime('birthday')->nullable ()->comment ('');
            $table->string('province')->nullable ()->comment ('');
            $table->string('city')->nullable ()->comment ('');
            $table->string('country')->nullable ()->comment ('');
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
        Schema::table('users', function (Blueprint $table) {
            //
        });

        Schema::dropIfExists('profiles');
    }
}
