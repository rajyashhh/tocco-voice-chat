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
        Schema::table('agent_salary_requests', function (Blueprint $table) {
            $table->dropForeign('agent_salary_requests_payment_gateway_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('agent_salary_requests', function (Blueprint $table) {
            $table->foreignId('payment_gateway_id')->constrained('payment_gateways', 'id')->cascadeOnDelete();
        });
    }
};
