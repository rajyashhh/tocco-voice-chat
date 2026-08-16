<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §6.1 pre-aggregation: one row per (date, bet_bucket) summarising
 * fair_luck_transactions so the reports page reads thousands of rows instead of
 * scanning the 12M-row raw table on every request (the §5 504 root cause).
 *
 * Money columns stay integer-domain (BIGINT — coins are whole numbers; the raw
 * table's DECIMAL(15,2) carries no fractional coins). rtp_effective is the only
 * derived ratio and is stored pre-computed so the page never divides at render.
 *
 * top_winners / top_losers hold the per-day top-10 net senders as JSON
 * ([{user_id, rounds, total_bets, net}, ...]); a single-day view shows them
 * exactly, a multi-day view merges and re-ranks these candidate lists in PHP
 * (no raw GROUP BY over millions of rows in a web request).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('fair_luck_daily_stats')) {
            return;
        }

        Schema::create('fair_luck_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            // Presentation bucket = lower bound of the bet-size band the row
            // aggregates (technical grouping for per-size RTP, not a money knob).
            $table->unsignedBigInteger('bet_bucket')->default(0);
            $table->unsignedBigInteger('bets_count')->default(0);
            $table->unsignedBigInteger('bets_sum')->default(0);
            $table->unsignedBigInteger('payout_sum')->default(0);
            $table->unsignedBigInteger('winners_count')->default(0);
            // RTP as a stored ratio (payout_sum / bets_sum), 4 dp.
            $table->decimal('rtp_effective', 8, 4)->default(0);
            $table->json('top_winners')->nullable();
            $table->json('top_losers')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Idempotent upsert key + the page's primary range read.
            $table->unique(['date', 'bet_bucket'], 'uq_flds_date_bucket');
            $table->index('date', 'idx_flds_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fair_luck_daily_stats');
    }
};
