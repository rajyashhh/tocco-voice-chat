<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_gifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('gift_id');  
            $table->unsignedBigInteger('user_id');  
            $table->integer('quantity')->default(1);
            $table->bigInteger('expire')->default(0);
            $table->timestamps();

            $table->foreign('gift_id')->references('id')->on('gifts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_gifts');
    }
};
