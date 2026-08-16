<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fairluck_loss_pool_totals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('lifetime_contributed')->default(0);
            $table->bigInteger('lifetime_paid_out')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fairluck_loss_pool_totals');
    }
};
