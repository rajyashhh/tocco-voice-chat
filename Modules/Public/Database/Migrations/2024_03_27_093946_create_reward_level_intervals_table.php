<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reward_level_intervals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_interval_id')->nullable()->constrained('level_intervals')->nullOnDelete();
            $table->string('type');
            $table->string('target');
            $table->unsignedTinyInteger('expire')->default(0);
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
        Schema::dropIfExists('rewardlevel_intervals');
    }
};
