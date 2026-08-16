<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable()->comment('نوع الإشعار من enum');
            $table->unsignedBigInteger('model_id')->nullable()->comment('معرّف العنصر المرتبط');
            $table->string('model_type')->nullable()->comment('نوع الموديل المرتبط');
            $table->text('title');
            $table->json('data')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_read')->default(false);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'is_read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};

