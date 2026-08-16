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
        Schema::create('special_id_histories', function (Blueprint $table) {
            $table->id();
            $table->boolean('status')->comment('1=>used 0=>unused');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->unsignedInteger('ware_id')->comment('معرف السلعة');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_id_histories');
    }
};
