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
        Schema::create('general_roles', function (Blueprint $table) {
            $table->id();
            $table->char("type",255)->nullable();
            $table->char("sub_type",255)->nullable();
            $table->text("desc_en")->nullable();
            $table->text("desc_ar")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_roles');
    }
};
