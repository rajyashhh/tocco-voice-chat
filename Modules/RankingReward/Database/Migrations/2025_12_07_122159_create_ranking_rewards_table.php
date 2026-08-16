<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRankingRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('ranking_rewards')) {
            Schema::create('ranking_rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ranking_range_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('target_type');
                $table->string('target');
                $table->integer('expire_days')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ranking_rewards');
    }
}
