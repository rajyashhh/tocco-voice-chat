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
        Schema::create('reward_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_event_id')->nullable()->constrained('charge_events')->nullOnDelete();
            $table->string('type');
            $table->string('target');
            $table->unsignedTinyInteger('expire')->default('1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_targets');
    }
};
