<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create processed_jobs table for Job Deduplication
 *
 * Purpose: Prevent duplicate execution of ProcessLuckyGiftPostJob
 * when Queue Workers retry or process the same job multiple times
 */
return new class extends Migration
{
    /**
     */
    public function up(): void
    {
        Schema::create('processed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_identifier', 255)->unique()->comment('Unique identifier for the job (MD5 hash of payload)');
            $table->string('job_type', 100)->index()->comment('Job class name');
            $table->timestamp('processed_at')->useCurrent()->index()->comment('When the job was first processed');

            $table->index(['job_type', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processed_jobs');
    }
};
