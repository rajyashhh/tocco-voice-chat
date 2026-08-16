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
        Schema::create('change_agency_mangers', function (Blueprint $table) {
            $table->id();
            $table->text('agencies_ids')->nullable();
            $table->unsignedBigInteger ('old_agency_manger_id')->nullable ();
            $table->unsignedBigInteger ('new_agency_manger_id')->nullable ();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_agency_mangers');
    }
};
