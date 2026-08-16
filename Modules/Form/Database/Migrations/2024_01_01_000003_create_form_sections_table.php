<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->onDelete('cascade');
            $table->json('title')->comment('Multi-language title {"en":"Personal Info","ar":"البيانات الشخصية"}');
            $table->json('description')->nullable()->comment('Multi-language description');
            $table->integer('section_order')->default(0)->comment('Order of section in form');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index('form_template_id');
            $table->index('section_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_sections');
    }
};
