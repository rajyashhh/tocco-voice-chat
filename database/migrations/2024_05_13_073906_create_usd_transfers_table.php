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
        Schema::create('usd_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_id')->comment('معرف حساب المسؤول')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('agency_id')->nullable();
            $table->integer("user_type")->default(0)->comment("0=>users,1=>agencies");
            $table->integer('value')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usd_transfers');
    }
};
