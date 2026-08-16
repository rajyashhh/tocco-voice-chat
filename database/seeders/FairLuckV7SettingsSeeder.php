<?php

namespace Database\Seeders;

use App\Models\FairLuckSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * V7 Single-Step Engine Settings Seeder.
 *
 * This is the AUTHORITATIVE seeder for production V7 settings.
 * Run: php artisan db:seed --class=FairLuckV7SettingsSeeder
 */
class FairLuckV7SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding FairLuck V7 Single-Step Engine settings...');

        $settings = [
            // === Fee Rates ===
            ['key' => 'V7_app_fee_rate', 'value' => 0.015, 'description' => 'App fee rate (1.5%). Deducted before game.'],
            ['key' => 'fair_luck_app_fee_rate', 'value' => 0.015, 'description' => 'App fee rate (legacy key, mirrors V7_app_fee_rate)'],
            ['key' => 'fair_luck_receiver_fee_rate', 'value' => 0.10, 'description' => 'Receiver payout rate (10% of total payout)'],
            ['key' => 'fair_luck_owner_fee_rate', 'value' => 0.10, 'description' => 'Host/owner payout rate (10% of total payout)'],

            // === Target RTP ===
            ['key' => 'V7_target_rtp', 'value' => 0.99, 'description' => 'Target RTP (99%). Per-user RTP correction aims for this.'],

            // === Wallet Zone Thresholds (coins) ===
            ['key' => 'V7_wallet_min', 'value' => 10000, 'description' => 'CRITICAL floor. Jackpot gate blocks payouts that would breach this.'],
            ['key' => 'V7_wallet_tight', 'value' => 50000, 'description' => 'TIGHT zone threshold. Wins suppressed.'],
            ['key' => 'V7_wallet_target', 'value' => 200000, 'description' => 'NORMAL zone target. Engine equilibrium point.'],
            ['key' => 'V7_wallet_high', 'value' => 500000, 'description' => 'GENEROUS zone threshold. Wins boosted.'],
            ['key' => 'V7_wallet_drain', 'value' => 1000000, 'description' => 'DRAIN zone threshold. Maximum win boosting.'],

            // === Negative Vault ===
            ['key' => 'V7_negative_limit', 'value' => 30000, 'description' => 'How far negative the vault can go. Default: 30,000 coins.'],
            ['key' => 'global_vault_negative_limit', 'value' => 30000, 'description' => 'Legacy negative limit (mirrors V7_negative_limit)'],

            // === Base Weights ===
            ['key' => 'V7_base_weights', 'value' => json_encode([
                '0'    => 93445,
                '5'    => 4000,
                '10'   => 1500,
                '20'   => 600,
                '50'   => 220,
                '100'  => 110,
                '250'  => 65,
                '500'  => 38,
                '1000' => 22,
            ]), 'description' => 'Base probability weights. Sum ~100,000. E[M]=1.2625.'],

            // === Weight Adjustment Sensitivity ===
            ['key' => 'V7_nowin_sensitivity', 'value' => 0.15, 'description' => 'How much 0x weight adjusts. Higher = more aggressive.'],
            ['key' => 'V7_win_base_sensitivity', 'value' => 0.7, 'description' => 'Base suppression factor when vault is low.'],
            ['key' => 'V7_win_position_sensitivity', 'value' => 2.0, 'description' => 'Extra suppression for high-tier multipliers.'],
            ['key' => 'V7_boost_base_sensitivity', 'value' => 0.5, 'description' => 'Base boost factor when vault is generous.'],
            ['key' => 'V7_boost_position_sensitivity', 'value' => 1.8, 'description' => 'Extra boost for high-tier multipliers when generous.'],
            ['key' => 'V7_wallet_weight', 'value' => 0.70, 'description' => 'Weight of wallet health in combined factor (0-1).'],
            ['key' => 'V7_rtp_weight', 'value' => 0.30, 'description' => 'Weight of user RTP correction in combined factor (0-1).'],
            ['key' => 'V7_nowin_floor', 'value' => 50000, 'description' => 'Minimum weight for 0x tier. Ensures wins never guaranteed.'],
            ['key' => 'V7_rtp_activation', 'value' => 500, 'description' => 'Min total bet before per-user RTP correction activates.'],

            // === Loss Streak Protection ===
            ['key' => 'V7_max_loss_streak', 'value' => 20, 'description' => 'Force a win after this many consecutive losses. 0=disabled.'],
            ['key' => 'V7_forced_win_mult', 'value' => 5, 'description' => 'Multiplier used for forced loss-streak wins (5x).'],

            // === Other ===
            ['key' => 'coin_to_usd_rate', 'value' => 0.01, 'description' => 'Coin to USD conversion rate for display.'],
        ];

        foreach ($settings as $s) {
            FairLuckSetting::updateOrCreate(['key' => $s['key']], [
                'value' => $s['value'],
                'description' => $s['description'],
            ]);
            $this->command->info("  + {$s['key']}");
        }

        Cache::forget('fair_luck:settings');

        $this->command->info('');
        $this->command->info("Seeded " . count($settings) . " V7 settings.");
        $this->command->info("Engine: Single-step weighted selection | Target RTP: 99%");
    }
}
