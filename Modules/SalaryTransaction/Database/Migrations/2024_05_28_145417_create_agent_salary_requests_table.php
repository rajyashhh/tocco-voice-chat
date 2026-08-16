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
        Schema::create('agent_salary_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger ('agency_id')->nullable ()->default (0);
            $table->unsignedBigInteger('agency_owner_id')->nullable();
            $table->integer('status')->comment("0=>waiting,1=>accepting,3=>rejected")->default(0);
            $table->integer('type')->comment("1=>coins,2=>reel mony")->default(1);
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways', 'id')->cascadeOnDelete();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->integer("usd")->default(0);
            $table->integer("coins")->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_salary_requests');
    }
};
