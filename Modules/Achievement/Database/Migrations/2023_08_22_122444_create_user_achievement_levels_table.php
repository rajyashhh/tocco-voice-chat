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
        Schema::create('user_achievement_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achievement_level_id')->constrained('achievement_levels')->cascadeOnDelete();
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('gift_achievement_id')->nullable()->constrained('gift_achievements')->onDelete('cascade');
            $table->string('unique_value')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_enable')->default(true);
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
        Schema::dropIfExists('user_achievement_levels');
    }
};
