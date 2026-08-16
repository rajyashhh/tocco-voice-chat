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
        Schema::create('moment_user_gifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moment_id')->constrained('moment')->onDelete('cascade');
            $table->bigInteger('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('gift_id')->unsigned()->index();
            $table->foreign('gift_id')->references('id')->on('gifts')->onDelete('cascade');
            $table->integer('num');
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
        Schema::table('moment_user_gifts', function (Blueprint $table) {
           $table->dropConstrainedForeignId('moment_id');
           $table->dropConstrainedForeignId('gift_id');
           $table->dropConstrainedForeignId('user_id');
        });
        Schema::dropIfExists('moment_user_gifts');
    }
};
