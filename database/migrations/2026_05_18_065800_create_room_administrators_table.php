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
        Schema::create('room_administrators', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('room_id')->comment('معرف الغرفة');
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم الأدمن');
            $table->unsignedBigInteger('assigned_by')->nullable()->comment('من قام بتعيين الأدمن');
            $table->timestamp('assigned_at')->useCurrent()->comment('وقت التعيين');
            $table->timestamps();

            // Indexes
            $table->index('room_id', 'idx_room_id');
            $table->index('user_id', 'idx_user_id');
            $table->index(['room_id', 'user_id'], 'idx_room_user');

            // Foreign keys
            $table->foreign('room_id', 'fk_room_administrators_room')
                ->references('id')->on('rooms')
                ->onDelete('cascade');

            $table->foreign('user_id', 'fk_room_administrators_user')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('assigned_by', 'fk_room_administrators_assigned_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            // Unique constraint to prevent duplicates
            $table->unique(['room_id', 'user_id'], 'unique_room_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_administrators');
    }
};
