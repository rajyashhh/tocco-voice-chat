<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Lucky-gift panel reduction: the engine is now driven by exactly THREE
 * owner-entered keys (fair_luck_owner_fee_rate, fair_luck_receiver_fee_rate,
 * V7_target_rtp). Everything else is either a code constant or derived by the
 * engine at runtime — the corresponding setting rows are pruned so no stale
 * knob can ever silently steer the money again.
 *
 * Also clamps an over-promised RTP down to the sustainable ceiling
 * (1 − owner − receiver), computed in PHP from the SAME settings snapshot
 * (never lexicographic SQL LEAST on the string column).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('fair_luck_settings')->whereIn('key', [
            'fair_luck_app_fee_rate', 'V7_app_fee_rate', 'V7_multiplier_weights',
            'V7_dynamic_adjust', 'V7_nowin_sensitivity', 'V7_win_base_sensitivity',
            'V7_win_position_sensitivity', 'V7_boost_base_sensitivity', 'V7_boost_position_sensitivity',
            'V7_wallet_weight', 'V7_rtp_weight', 'V7_nowin_floor',
            'V7_wallet_min', 'V7_wallet_tight', 'V7_wallet_target', 'V7_wallet_high', 'V7_wallet_drain',
            'V7_base_weights', 'V7_multipliers', 'V7_max_loss_streak', 'V7_forced_win_mult',
            'V7_loss_streak_forced_mult', 'V7_rtp_activation', 'coin_to_usd_rate',
            'V7_wallet_healthy_max_mult', 'V7_wallet_moderate_max_mult',
            'V7_wallet_low_max_mult', 'V7_wallet_critical_max_mult',
            'V7_min_prob_when_low', 'V7_min_bets_100x', 'V7_min_bets_500x', 'V7_max_single_win_pct',
            'wallet_healthy_usd', 'wallet_warning_usd', 'wallet_critical_usd', 'wallet_max_negative_usd',
            'bankruptcy_min_safe_balance', 'bankruptcy_critical_threshold', 'bankruptcy_max_payout_percentage',
            'v6_max_probability_cap', 'fairluck_jackpot_cooldown_bets', 'v6_target_rtp',
        ])->delete();

        // Canonical 3 keys — seed only if absent (existing owner values stay).
        foreach ([
            'fair_luck_owner_fee_rate' => '0.01',
            'fair_luck_receiver_fee_rate' => '0.10',
            'V7_target_rtp' => '0.89',
        ] as $key => $value) {
            DB::table('fair_luck_settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Clamp the stored RTP to the sustainable ceiling derived from the SAME
        // snapshot of the live rates (panel RTP above it is what drained the vault).
        $settings = DB::table('fair_luck_settings')
            ->whereIn('key', ['fair_luck_owner_fee_rate', 'fair_luck_receiver_fee_rate', 'V7_target_rtp'])
            ->pluck('value', 'key');
        $ceiling = round(1.0 - (float) $settings['fair_luck_owner_fee_rate'] - (float) $settings['fair_luck_receiver_fee_rate'], 4);
        if ((float) $settings['V7_target_rtp'] > $ceiling) {
            DB::table('fair_luck_settings')->where('key', 'V7_target_rtp')->update(['value' => (string) $ceiling]);
        }

        // Raw-save pollution left in `settings` by the old GiftController v1 loop
        // (these keys were saved RAW there while the engine read fair_luck_settings).
        $settingsKeys = [
            'app_wallet_lucky_gift', 'owner_lucky_gift', 'host_lucky_gift',
            'fair_luck_app_fee_rate', 'fair_luck_owner_fee_rate', 'fair_luck_receiver_fee_rate',
        ];
        DB::table('settings')->whereIn('key', $settingsKeys)->delete();

        Cache::forget('fair_luck:settings');
        foreach ($settingsKeys as $key) {
            Cache::forget("percentage_{$key}"); // forever-cache used by getGiftPercentage()
            Cache::forget($key);
        }
    }

    public function down(): void
    {
        // Pruned knobs are intentionally unrecoverable (the engine derives them).
        Cache::forget('fair_luck:settings');
    }
};
