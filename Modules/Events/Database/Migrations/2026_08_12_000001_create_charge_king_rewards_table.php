<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_king_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('rank');
            $table->string('type');
            $table->string('target');
            $table->string('sub_type')->nullable();
            $table->string('gender')->default('all');
            $table->string('expire');
            $table->timestamps();

            $table->index('rank');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_king_rewards');
    }
};
