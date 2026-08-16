<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiftLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedTinyInteger('type')->default('2')->comment('1 جوهرة 2 هدية')->nullable();
            $table->integer('giftId')->index()->comment('معرف الهدية');
            $table->integer('roomowner_id')->index()->comment('معرف صاحب الغرفة');
            $table->string('giftName')->comment('اسم الهدية');
            $table->unsignedInteger('giftNum')->comment('كمية الهدية');
            $table->unsignedDecimal('giftPrice',12,2)->comment('سعر الهدية');
            $table->unsignedInteger('sender_id')->comment('هوية مرسل الهدية');
            $table->unsignedInteger('receiver_id')->index()->comment('معرف المستلم');
            $table->unsignedTinyInteger('is_play')->default('2')->comment('1 بث 2 لا يبث')->nullable();
            $table->unsignedDecimal('platform_obtain',12,2)->comment('المبلغ الذي حصلت عليه المنصة')->nullable();
            $table->unsignedDecimal('receiver_obtain',12,2)->comment('المبلغ الذي حصل عليه المستلم')->nullable();
            $table->unsignedDecimal('roomowner_obtain',12,2)->comment('دخل صاحب الغرفة')->nullable();
            $table->integer('union_id')->comment('معرف النقابة')->nullable();
            $table->integer('family_id')->comment('معرف االاسرة')->nullable();
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
        Schema::dropIfExists('gift_logs');
    }
}
