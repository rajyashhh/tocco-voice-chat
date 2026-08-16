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
        Schema::create('return_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->double('coins');
            $table->double('usd');
            $table->unsignedBigInteger('charger_id');
            $table->string('charger_type');
            $table->double('charger_amount')->nullable();
            $table->unsignedBigInteger('receiver_id');
            $table->string('receiver_type');
            $table->double('receiver_amount')->nullable();
            $table->unsignedBigInteger('charge_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_charges');
    }
};
