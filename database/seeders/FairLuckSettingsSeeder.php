<?php


namespace Database\Seeders;

use App\Models\FairLuckSetting;
use Illuminate\Database\Seeder;

class FairLuckSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'target_loss_rate',
                'value' => '0.01',
                'description' => 'Target user loss rate (1% = 0.01)',
            ],
            [
                'key' => 'app_profit_rate',
                'value' => '0.01',
                'description' => 'App profit target (1% = 0.01)',
            ],
            [
                'key' => 'beginner_protection_days',
                'value' => '7',
                'description' => 'Days of protection for new users',
            ],
            [
                'key' => 'beginner_protection_bets',
                'value' => '50',
                'description' => 'Maximum bets in protection period',
            ],
            [
                'key' => 'beginner_max_profit_rate',
                'value' => '0.20',
                'description' => 'Max profit rate before protection ends (20% = 0.20)',
            ],
            [
                'key' => 'beginner_boost_multiplier',
                'value' => '2.0',
                'description' => 'Win probability boost for beginners (e.g., 2.0 = double chance)',
            ],
            [
                'key' => 'available_multipliers',
                'value' => '[5, 10, 20, 50, 100, 250, 500, 1000]',
                'description' => 'Available win multipliers',
            ],
            [
                'key' => 'adjustment_factor',
                'value' => '0.5',
                'description' => 'How strongly deviation affects probability',
            ],
            [
                'key' => 'v6_target_rtp',
                'value' => '0.85',
                'description' => 'Target RTP for V6 algorithm (85% = 0.85)',
            ]
        ];

        foreach ($settings as $setting) {
            FairLuckSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
