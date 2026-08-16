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
        Schema::create('leave_agency_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('agency_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('admin_id');
            $table->unsignedInteger('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_agency_requests');
    }
};
