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
        Schema::create('charge_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('charge_id');
            $table->unsignedBigInteger('user_id');
            $table->longText('reason_en')->nullable();
            $table->longText('reason_ar')->nullable();
            $table->longText('invoice')->nullable();
            $table->string('type')->comment('Type of owner: either "agency" or "user"');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_invoices');
    }
};
