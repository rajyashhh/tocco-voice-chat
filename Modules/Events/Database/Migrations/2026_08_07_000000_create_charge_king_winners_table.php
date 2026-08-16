<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charge King: pays the single top charger of the previous calendar month a
 * coins prize (amount configurable via the `charge_king_prize` setting), once
 * per month. This table records each payout so the monthly command never pays
 * the same month twice, even if it runs more than once.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charge_king_winners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('month', 7);
            $table->unsignedBigInteger('prize');
            $table->timestamps();

            $table->unique('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_king_winners');
    }
};
