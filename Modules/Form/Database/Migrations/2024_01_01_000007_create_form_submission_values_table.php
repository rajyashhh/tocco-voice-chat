<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('form_submissions')->onDelete('cascade');
            $table->foreignId('field_id')->constrained('form_fields')->onDelete('cascade');
            $table->text('field_value')->nullable()->comment('Text value for most field types');
            $table->string('file_path', 500)->nullable()->comment('File path for file uploads');
            $table->string('file_name')->nullable()->comment('Original file name');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->timestamps();

            $table->index('submission_id');
            $table->index('field_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_values');
    }
};
