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
//        Schema::create('special_users', function (Blueprint $table) {
//            $table->id();
//            $table->unsignedBigInteger('user_id')->index();
//            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
//            $table->boolean('status')->default(true);
//            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        Schema::dropIfExists('special_users');
    }
};
