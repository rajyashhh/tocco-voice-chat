<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTribeRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tribe_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tribe_top_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('type', ['agency_reward', 'share_rewards']);
            $table->string('target_type');
            $table->string('target');
            $table->integer('quantity');
            $table->integer('expire_days');
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
        Schema::dropIfExists('tribe_rewards');
    }
}
