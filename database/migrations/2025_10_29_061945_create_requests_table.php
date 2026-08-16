<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_template_id');
            $table->unsignedBigInteger('submitted_by');
            $table->string('bd_id')->nullable();
            $table->string('name')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('form_template_type');
            $table->json('data');
            $table->string('country')->nullable(); 
            $table->string('status')->default('pending'); 
            $table->timestamps();

            $table->foreign('form_template_id')->references('id')->on('form_templates')->onDelete('cascade');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_requests');
    }
};
