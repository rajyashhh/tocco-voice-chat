<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->onDelete('cascade');
            $table->unsignedBigInteger('entity_id')->comment('Reference to agencies.id, users.id, etc.');
            $table->string('entity_type', 50)->comment('Entity type: agency, user, product, etc.');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->enum('submission_status', ['draft', 'submitted', 'approved', 'rejected'])->default('submitted');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('form_template_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('submitted_by');
            $table->index('submission_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
