<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHostLevelRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('host_level_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_level_id')->nullable()->constrained('host_levels')->nullOnDelete();
            $table->string('type');
            $table->string('target');
            $table->integer('expire')->default('1');
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
        Schema::dropIfExists('host_level_rewards');
    }
}
