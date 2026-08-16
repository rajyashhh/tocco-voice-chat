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
        Schema::create('fair_luck_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index()->constrained()->onDelete('cascade');
            $table->unsignedInteger('gift_id')->index();
            $table->foreign('gift_id')->references('id')->on('gifts')->onDelete('cascade');
            $table->decimal('bet_amount', 15, 2);
            $table->boolean('is_winner')->default(false)->index();
            $table->unsignedInteger('multiplier')->nullable();
            $table->decimal('profit_amount', 15, 2);
            $table->decimal('deviation_before', 10, 6);
            $table->decimal('calculated_probability', 5, 4);
            $table->boolean('is_beginner_protected')->default(false)->index();
            $table->decimal('protection_multiplier', 3, 2)->default(1.0);
            $table->unsignedInteger('room_id')->nullable()->index();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fair_luck_transactions');
    }
};
