<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoomBoomRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_boom_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_boom_level_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('target');
            $table->string('target_type');
            $table->tinyInteger('priority');
            $table->integer('quantity');
            $table->integer('expire_days')->nullable()->default(0);
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
        Schema::dropIfExists('room_boom_rewards');
    }
}
