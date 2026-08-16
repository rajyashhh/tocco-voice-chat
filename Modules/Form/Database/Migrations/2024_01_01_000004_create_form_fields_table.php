<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('form_sections')->onDelete('cascade');
            $table->json('field_label')->comment('Multi-language label {"en":"Full Name","ar":"الاسم بالكامل"}');
            $table->string('field_name', 100)->comment('Database identifier/slug');
            $table->string('field_type', 50)->comment('text, email, number, tel, date, textarea, file, select, checkbox, radio');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_enabled')->default(true);
            $table->integer('field_order')->default(0)->comment('Order within section');
            $table->json('placeholder')->nullable()->comment('Multi-language placeholder {"en":"Enter name","ar":"أدخل الاسم"}');
            $table->json('help_text')->nullable()->comment('Multi-language help text');
            $table->json('validation_rules')->nullable()->comment('JSON validation rules');
            $table->json('options')->nullable()->comment('Multi-language options for select/radio/checkbox');
            $table->string('default_value')->nullable();
            $table->timestamps();

            $table->index('section_id');
            $table->index('field_order');
            $table->index('field_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
