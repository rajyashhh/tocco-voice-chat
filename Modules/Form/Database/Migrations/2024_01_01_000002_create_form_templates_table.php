<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->json('title')->comment('Multi-language title {"en":"Agency Form","ar":"نموذج الوكالة"}');
            $table->string('form_type', 50)->comment('agency, user, product, etc.');
            $table->json('description')->nullable()->comment('Multi-language description');
            $table->json('admin_notice')->nullable()->comment('Multi-language notice');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('form_type');
            $table->index('is_active');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
