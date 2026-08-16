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
        if (Schema::hasTable('room_cup_rewards')) {
            return;
        }
        Schema::create('room_cup_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('total_room_gift_id')->nullable();
            $table->unsignedBigInteger('user_id'); 
            $table->enum('type', ['owner', 'admin']); 
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();


            $table->foreign('total_room_gift_id')
                ->references('id')
                ->on('total_room_gifts')
                ->onDelete('cascade');

            $table->index(['room_id', 'user_id','total_room_gift_id']);
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_cup_rewards');
    }
};
