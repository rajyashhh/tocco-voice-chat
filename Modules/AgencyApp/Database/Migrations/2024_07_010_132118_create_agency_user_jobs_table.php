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
        Schema::create('agency_user_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id')->constrained('agencies')->onDelete ('cascade');
            $table->foreignId ('user_id')->constrained('users')->onDelete ('cascade');
            $table->char('type',100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agency_user_jobs');
    }
};
