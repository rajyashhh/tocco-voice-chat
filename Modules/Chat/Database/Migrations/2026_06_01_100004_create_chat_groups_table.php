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
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('chat_room_id');

            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->string('avatar', 255)->nullable();

            $table->unsignedBigInteger('owner_id');

            $table->enum('privacy', ['public', 'private'])->default('private');
            $table->enum('join_policy', ['open', 'approval', 'invite_only'])->default('invite_only');

            $table->string('invite_token', 64)->nullable();

            $table->unsignedInteger('members_count')->default(0);
            $table->unsignedInteger('max_members')->default(256);

            $table->boolean('only_admins_post')->default(false);

            $table->unsignedBigInteger('pinned_message_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('chat_room_id', 'uq_group_room');
            $table->unique('invite_token', 'uq_group_invite_token');
            $table->index('owner_id', 'idx_owner');

            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_groups');
    }
};
