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
        Schema::create('streaming_analytics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();

            // Room stats
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('total_session_minutes')->default(0);
            $table->unsignedInteger('peak_concurrent_rooms')->default(0);

            // Participant stats
            $table->unsignedInteger('total_participants')->default(0);
            $table->unsignedInteger('total_participant_minutes')->default(0);
            $table->unsignedInteger('unique_participants')->default(0);

            // Track stats (video quality)
            $table->unsignedInteger('tracks_sd')->default(0);
            $table->unsignedInteger('tracks_hd')->default(0);
            $table->unsignedInteger('tracks_fhd')->default(0);
            $table->unsignedInteger('tracks_2k')->default(0);
            $table->unsignedInteger('tracks_2k_plus')->default(0);
            $table->unsignedInteger('tracks_audio')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streaming_analytics');
    }
};
