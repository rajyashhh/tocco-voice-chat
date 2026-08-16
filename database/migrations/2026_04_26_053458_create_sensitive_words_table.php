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
        Schema::create('sensitive_words', function (Blueprint $table) {
            $table->id();
            $table->json('word');
            $table->string('replacement')->default('***');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium')->comment('filter=replace with *, block=reject message, warn=allow but flag');
            $table->enum('action', ['filter', 'warn', 'block'])->default('filter')->comment('What to replace the word with if action=filter');
            $table->boolean('is_active')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensitive_words');
    }
};
