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
        Schema::create('pk_winners', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('pk_event_id');
            $table->unsignedInteger('user_id');
            $table->integer('level');
            $table->string('pk_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pk_winners');
    }
};
