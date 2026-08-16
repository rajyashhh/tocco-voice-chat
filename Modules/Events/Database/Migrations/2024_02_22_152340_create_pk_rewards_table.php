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
        Schema::create('pk_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pk_event_id')->nullable()->constrained('pk_events')->nullOnDelete();
            $table->string('type');
            $table->integer('level');
            $table->string('target');
            $table->unsignedTinyInteger('expire')->default('1');
            $table->string('pk_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pk_rewards');
    }
};
