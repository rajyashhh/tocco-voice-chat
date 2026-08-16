<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manger_type_badge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manger_type_id')
                ->constrained('manger_types')
                ->onDelete('cascade');
            $table->foreignId('badge_id')
                ->constrained('badges')
                ->onDelete('cascade');
            $table->integer('expire')->default(0);
            $table->timestamps();

            $table->unique(['manger_type_id', 'badge_id']);
            $table->index('manger_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manger_type_badge');
    }
};
