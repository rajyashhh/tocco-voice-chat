<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGmOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gm_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('skill_apply_id')->comment('user skill id')->nullable();
            $table->string('order_no')->nullable();
            $table->unsignedInteger('user_id')->index()->comment('consumer')->nullable();
            $table->unsignedInteger('master_id')->index()->nullable();
            $table->unsignedTinyInteger('status')->default('1')->nullable();
            $table->unsignedInteger('skill_id')->nullable();
            $table->unsignedInteger('start_time')->comment('service hours')->nullable();
            $table->unsignedInteger('num')->default('1')->comment('quantity')->nullable();
            $table->string('remarks')->default('remark')->comment('Remark')->nullable();
            $table->unsignedInteger('price')->comment('unit price')->nullable();
            $table->string('unit')->default('unit')->nullable();
            $table->unsignedInteger('total_price')->nullable();
            $table->decimal('fee')->comment('handling fee')->nullable();
            $table->decimal('real_price')->comment('Actual credited amount')->nullable();
            $table->decimal('refund')->comment('refund amount')->nullable();
            $table->unsignedTinyInteger('pay_type')->default('4')->comment('1 google pay 2 apple pay 3 Balance 4 Pending payment')->nullable();
            $table->unsignedTinyInteger('is_first')->comment('Immediate service: 0 not applied 1 applied')->nullable();
            $table->unsignedTinyInteger('is_discuss')->comment('Rating: 0 not rated 1 rated')->nullable();
            $table->unsignedTinyInteger('is_notify')->comment('Whether the opening notification has been sent')->nullable();
            $table->string('cancel')->default('c')->comment('Reason for Cancellation')->nullable();
            $table->unsignedInteger('coupon_id')->nullable();
            $table->decimal('coupon_price')->comment('Discounted price')->nullable();
            $table->string('reason')->default('r')->comment('سبب الاستئناف')->nullable();
            $table->string('images')->comment('لقطة من الاستئناف')->nullable();
            $table->unsignedInteger('f_user_id')->nullable();
            $table->string('out_refund_no')->comment('رقم طلب رد الأموال')->nullable();
            $table->unsignedInteger('addtime')->comment('وقت الطلب')->nullable();
            $table->unsignedInteger('paytime')->comment('وقت الدفع')->nullable();
            $table->unsignedInteger('refusetime')->comment('وقت رفض الاسترداد')->nullable();
            $table->unsignedInteger('finishtime')->comment('وقت الاكتمال')->nullable();
            $table->unsignedInteger('union_id')->comment('معرف النقابة')->nullable();
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
        Schema::dropIfExists('gm_orders');
    }
}
