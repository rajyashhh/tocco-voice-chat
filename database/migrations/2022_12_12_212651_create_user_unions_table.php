<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserUnionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_unions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('union_id')->index()->comment('guild id');
            $table->unsignedInteger('user_id');
            $table->decimal('total_price')->default('0.00')->comment('lump sum')->nullable();
            $table->decimal('settlement_price')->default('0.00')->comment('Settled amount')->nullable();
            $table->dateTime('check_time')->comment('Review time');
            $table->string('check_content')->comment('grounds for refusal')->nullable();
            $table->unsignedTinyInteger('check_uid')->comment('Audit user')->nullable();
            $table->string('check_status')->comment('0 not reviewed 1 reviewed 2 rejected');
            $table->decimal('di')->nullable();
            $table->decimal('coins')->nullable();
            $table->decimal('room_coins')->nullable();
            $table->decimal('flowers')->nullable();
            $table->decimal('flowers_value')->nullable();
            $table->decimal('gold')->nullable();
            $table->decimal('unsettled_price')->comment('outstanding amount')->nullable();
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
        Schema::dropIfExists('user_unions');
    }
}
