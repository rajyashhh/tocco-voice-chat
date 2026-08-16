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
        Schema::create('streaming_room_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->index();
            $table->string('room_sid')->unique();
            $table->unsignedBigInteger('owner_user_id')->nullable(); // صاحب الغرفة

            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable(); // calculated

            $table->unsignedInteger('peak_participants')->default(0); // أقصى عدد مشاركين
            $table->unsignedInteger('total_participants')->default(0); // عدد المشاركين الكلي

            $table->json('metadata')->nullable(); // بيانات إضافية

            $table->timestamps();

            $table->index(['owner_user_id', 'started_at']);
            $table->index('finished_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streaming_room_sessions');
    }
};
