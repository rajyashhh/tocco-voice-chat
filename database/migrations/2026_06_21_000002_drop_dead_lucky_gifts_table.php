<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the pre-V7 per-gift lucky config table. It only ever held win_probability +
 * min_percentage (min/mid/max multiplier tiers) which the V7 FairLuck engine never
 * reads — all lucky-gift economy is now central (RTP + MultiplierTable + fee rates).
 * All code references (Gift::luckyGift/lucky_gift relations, the LuckyGift model,
 * every eager-load, and the admin form/detail/write paths) were removed first, so
 * the table is pure dead weight. down() restores the empty structure (not the dead
 * data, which is intentionally discarded).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('lucky_gifts');
    }

    public function down(): void
    {
        Schema::create('lucky_gifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gift_id')->nullable();
            $table->integer('win_probability')->default(10);
            $table->text('min_percentage')->nullable();
            $table->timestamps();
        });
    }
};
