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
        Schema::create('streaming_tracks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_session_id')->constrained('streaming_room_sessions')->cascadeOnDelete();

            $table->string('participant_identity')->index();
            $table->enum('track_type', ['audio', 'video'])->index();

            $table->string('video_quality')->nullable(); // sd, hd, fhd, 2k, 2k_plus, 4k
            $table->unsignedInteger('video_width')->nullable();
            $table->unsignedInteger('video_height')->nullable();

            $table->timestamp('published_at');
            $table->timestamp('unpublished_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable(); // calculated

            $table->timestamps();

            $table->index(['track_type', 'video_quality']);
            $table->index(['room_session_id', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streaming_tracks');
    }
};
