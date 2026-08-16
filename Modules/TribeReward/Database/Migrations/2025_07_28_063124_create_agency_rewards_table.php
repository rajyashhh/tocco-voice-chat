<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgencyRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agency_rewards', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('agency_id');
            $table->enum('type', ['agency_reward', 'share_rewards']);
            $table->enum('target_type', ['vip', 'ware', 'achievement']);
            $table->string('target');
            $table->integer('quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->integer('expire_days');
            $table->dateTime('expire_at')->nullable();
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
        Schema::dropIfExists('agency_rewards');
    }
}
