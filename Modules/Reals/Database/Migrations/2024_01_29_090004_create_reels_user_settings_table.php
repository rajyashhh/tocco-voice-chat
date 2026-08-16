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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('real_type');
        });
        Schema::create('reels_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('all_unique_value')->nullable();
            $table->string('following_unique_value')->nullable();
            $table->integer('last_all_reel_id')->nullable();
            $table->integer('last_following_reel_id')->nullable();
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('real_type')->nullable()->default(0);
        });
        Schema::dropIfExists('reels_user_settings');
    }
};
