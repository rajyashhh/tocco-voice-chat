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
        Schema::create('salary_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger ('agency_id')->nullable ()->default (0);
            $table->unsignedBigInteger('host_id')->index();
            $table->foreign('host_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('status')->comment("0=>waiting,1=>accepting,2=>transferred,3=>completed,4=>rejected")->default(0);
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('agency_owner_id')->nullable();
            $table->integer("usd")->default(0);
            $table->integer("coins")->default(0);
            $table->string("bill_image")->nullable();
            $table->integer("host_check")->comment("0=>لم يتم اكشن من قبل الhost ,1=>confirm,2=>rejected")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_requests');
    }
};
