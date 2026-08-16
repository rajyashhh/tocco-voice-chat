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
        Schema::create('weekly_stars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('editor_id')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weekly_stars');
    }
};
