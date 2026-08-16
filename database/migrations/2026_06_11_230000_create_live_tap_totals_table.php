<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-broadcast tap-hearts (التكبيس) totals — one row per ended live, the
 * durable input for the future points system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_tap_totals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id')->index();
            $table->unsignedBigInteger('owner_id')->index();
            $table->unsignedBigInteger('total');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_tap_totals');
    }
};
