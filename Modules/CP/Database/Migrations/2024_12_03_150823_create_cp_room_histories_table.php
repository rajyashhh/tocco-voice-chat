<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cp_room_histories', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("room_id");
            $table->bigInteger("user_one_id");
            $table->bigInteger("user_two_id");
            $table->integer("index1");
            $table->integer("index2");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cp_room_histories');
    }
};
