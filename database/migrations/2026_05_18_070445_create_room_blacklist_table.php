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
        Schema::create('room_blacklist', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('room_id')->comment('معرف الغرفة');
            $table->unsignedBigInteger('user_id')->comment('معرف المستخدم المحظور');
            $table->unsignedBigInteger('banned_by')->nullable()->comment('من قام بالحظر');
            $table->timestamp('banned_at')->useCurrent()->comment('وقت الحظر');
            $table->unsignedInteger('duration_seconds')->nullable()->comment('مدة الحظر بالثواني، NULL للحظر الدائم');
            $table->timestamp('expires_at')->nullable()->comment('وقت انتهاء الحظر، NULL للحظر الدائم');
            $table->string('reason', 255)->nullable()->comment('سبب الحظر');
            $table->boolean('is_active')->default(true)->comment('هل الحظر نشط');
            $table->timestamps();

            // Indexes
            $table->index('room_id', 'idx_room_id');
            $table->index('user_id', 'idx_user_id');
            $table->index(['room_id', 'user_id'], 'idx_room_user');
            $table->index('expires_at', 'idx_expires_at');
            $table->index(['room_id', 'is_active', 'expires_at'], 'idx_active_bans');

            // Foreign keys
            $table->foreign('room_id', 'fk_room_blacklist_room')
                ->references('id')->on('rooms')
                ->onDelete('cascade');

            $table->foreign('user_id', 'fk_room_blacklist_user')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('banned_by', 'fk_room_blacklist_banned_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_blacklist');
    }
};
