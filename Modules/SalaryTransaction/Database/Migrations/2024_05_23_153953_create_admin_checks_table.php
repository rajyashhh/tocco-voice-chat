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
        Schema::create('admin_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId ('request_id')->constrained('salary_requests')->onDelete ('cascade');
            $table->integer("admin_check")->default(0)->comment("1=>checked");
            $table->string("type")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_checks');
    }
};
