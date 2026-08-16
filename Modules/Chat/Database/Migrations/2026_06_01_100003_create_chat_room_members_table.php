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
        Schema::create('chat_room_members', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('chat_room_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('role', ['owner', 'admin', 'member'])->default('member');
            $table->enum('status', ['active', 'muted', 'banned', 'left'])->default('active');

            $table->timestamp('muted_until')->nullable();

            $table->unsignedBigInteger('last_read_seq')->default(0);
            $table->unsignedBigInteger('last_delivered_seq')->default(0);
            $table->unsignedBigInteger('cleared_seq')->default(0);

            $table->unsignedBigInteger('invited_by')->nullable();

            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            $table->unique(['chat_room_id', 'user_id'], 'uq_room_user');
            $table->index(['user_id', 'status'], 'idx_user_rooms');
            $table->index(['chat_room_id', 'role'], 'idx_room_role');
            $table->index(['chat_room_id', 'last_read_seq'], 'idx_room_read');

            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_room_members');
    }
};
