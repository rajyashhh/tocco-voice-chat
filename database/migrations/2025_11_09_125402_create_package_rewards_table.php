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
        if (!Schema::hasTable('package_rewards')) {
            Schema::create('package_rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('super_package_id')->nullable()->constrained('super_package_rewards')->nullOnDelete();
                $table->string('type');
                $table->string('target');
                $table->unsignedTinyInteger('expire')->default('1');
                $table->integer('quantity');
                $table->timestamps();
            });
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_rewards');
    }
};
