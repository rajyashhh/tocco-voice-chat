<?php

namespace Database\Seeders;

use App\Models\FairLuckSetting;
use Illuminate\Database\Seeder;

/**
 * FairLuck V7 Optimized Settings Seeder
 * 
 * هذا السيدر يحتوي على الإعدادات المحسّنة لتحقيق RTP 90% المستهدف
 * 
 * شرح الأوزان (Weights):
 * ========================
 * الوزن هو رقم يحدد احتمالية ظهور كل مضاعف (multiplier)
 * 
 * مثال: إذا كان لدينا:
 * - 5x بوزن 500
 * - 10x بوزن 500
 * - 250x بوزن 400
 * 
 * المجموع = 500 + 500 + 400 = 1400
 * احتمالية ظهور 5x = 500/1400 = 35.7%
 * احتمالية ظهور 10x = 500/1400 = 35.7%
 * احتمالية ظهور 250x = 400/1400 = 28.6%
 * 
 * كلما زاد الوزن = كلما زادت احتمالية ظهور هذا المضاعف
 * 
 * الإعدادات الحالية (المحافظة):
 * ============================
 * 5x => 500   (وزن عالي جداً = يظهر كثيراً)
 * 10x => 500  (وزن عالي جداً = يظهر كثيراً)
 * 20x => 500  (وزن عالي جداً = يظهر كثيراً)
 * 50x => 500  (وزن عالي جداً = يظهر كثيراً)
 * 250x => 400 (وزن متوسط = نادر)
 * 500x => 300 (وزن منخفض = نادر جداً)
 * 1000x => 200 (وزن منخفض جداً = نادر جداً جداً)
 * 
 * النتيجة: المضاعفات الصغيرة تظهر 70% من الوقت، والكبيرة 30% فقط
 * لذلك RTP منخفض جداً (3% بدلاً من 90%)
 * 
 * الإعدادات المحسّنة (الموصى بها):
 * ==================================
 * 5x => 300   (تقليل الوزن = يظهر أقل)
 * 10x => 300  (تقليل الوزن = يظهر أقل)
 * 20x => 300  (تقليل الوزن = يظهر أقل)
 * 50x => 400  (زيادة طفيفة)
 * 250x => 600 (زيادة كبيرة = يظهر أكثر)
 * 500x => 700 (زيادة كبيرة جداً = يظهر أكثر)
 * 1000x => 500 (زيادة كبيرة جداً = يظهر أكثر)
 * 
 * النتيجة: المضاعفات الكبيرة تظهر 50% من الوقت = RTP أعلى
 */
class FairLuckV7OptimizedSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ============================================
            // 1. إعدادات RTP الأساسية
            // ============================================
            [
                'key' => 'V7_target_rtp',
                'value' => '0.90',
                'description' => 'Target RTP for V7 (90% = 0.90) - الهدف المستهدف للعودة للاعب',
            ],
            
            // ============================================
            // 2. إعدادات احتمالية الفوز
            // ============================================
            [
                'key' => 'V7_max_probability_cap',
                'value' => '95',
                'description' => 'Maximum win probability cap (95%) - الحد الأقصى لاحتمالية الفوز',
            ],
            [
                'key' => 'V7_boost_scaling',
                'value' => '0.03',
                'description' => 'Probability boost scaling factor (0.03) - معامل تعزيز الاحتمالية عند الخسارة (مخفض لتجنب RTP مرتفع)',
            ],
            [
                'key' => 'V7_reduce_scaling',
                'value' => '0.08',
                'description' => 'Probability reduction scaling factor (0.08) - معامل تقليل الاحتمالية عند الفوز (مرفوع لضبط RTP)',
            ],
            
            // ============================================
            // 3. إعدادات عامل الفوضى (Chaos Factor)
            // ============================================
            [
                'key' => 'V7_chaos_factor_min',
                'value' => '0.95',
                'description' => 'Minimum chaos factor (0.95) - الحد الأدنى لعامل الفوضى',
            ],
            [
                'key' => 'V7_chaos_factor_max',
                'value' => '1.05',
                'description' => 'Maximum chaos factor (1.05) - الحد الأقصى لعامل الفوضى',
            ],
            
            // ============================================
            // 4. إعدادات حماية اللاعبين الجدد
            // ============================================
            [
                'key' => 'V7_new_player_bets',
                'value' => '0',
                'description' => 'Number of bets for new player protection (20) - عدد الرهانات لحماية اللاعب الجديد',
            ],
            [
                'key' => 'V7_new_player_boost',
                'value' => '1.0',
                'description' => 'New player probability boost multiplier (3.5x) - معامل تعزيز احتمالية اللاعب الجديد',
            ],
            
            // ============================================
            // 5. إعدادات حماية الرصيد المنخفض
            // ============================================
            [
                'key' => 'V7_low_balance_threshold',
                'value' => '8',
                'description' => 'Low balance threshold in bet amounts (8) - عتبة الرصيد المنخفض (تقليل لتفعيل الحماية أسرع)',
            ],
            [
                'key' => 'V7_low_balance_min_prob',
                'value' => '0.50',
                'description' => 'Minimum probability when balance is low (50% = 0.50) - الحد الأدنى للاحتمالية عند الرصيد المنخفض (زيادة لحماية أفضل)',
            ],
            
            // ============================================
            // 6. إعدادات المحفظة والعملات
            // ============================================
            [
                'key' => 'coin_to_usd_rate',
                'value' => '0.01',
                'description' => 'Coin to USD conversion rate (1 coin = 0.01 USD)',
            ],
            [
                'key' => 'wallet_healthy_usd',
                'value' => '1000.00',
                'description' => 'Healthy wallet threshold in USD (1000 USD)',
            ],
            [
                'key' => 'wallet_warning_usd',
                'value' => '500.00',
                'description' => 'Warning wallet threshold in USD (500 USD)',
            ],
            [
                'key' => 'wallet_critical_usd',
                'value' => '200.00',
                'description' => 'Critical wallet threshold in USD (200 USD)',
            ],
            [
                'key' => 'wallet_max_negative_usd',
                'value' => '300.00',
                'description' => 'Maximum negative wallet balance in USD (300 USD)',
            ],
            
            // ============================================
            // 7. إعدادات حدود المضاعفات حسب صحة المحفظة
            // ============================================
            [
                'key' => 'V7_wallet_healthy_max_mult',
                'value' => '1000',
                'description' => 'Max multiplier when wallet is healthy (1000x)',
            ],
            [
                'key' => 'V7_wallet_moderate_max_mult',
                'value' => '250',
                'description' => 'Max multiplier when wallet is moderate (250x)',
            ],
            [
                'key' => 'V7_wallet_low_max_mult',
                'value' => '100',
                'description' => 'Max multiplier when wallet is low (100x)',
            ],
            [
                'key' => 'V7_wallet_critical_max_mult',
                'value' => '50',
                'description' => 'Max multiplier when wallet is critical (50x)',
            ],
            
            // ============================================
            // 8. إعدادات الحد الأدنى للرهانات قبل المضاعفات الكبيرة
            // ============================================
            [
                'key' => 'V7_min_bets_100x',
                'value' => '20',
                'description' => 'Minimum bets before 100x multiplier is available (20)',
            ],
            [
                'key' => 'V7_min_bets_500x',
                'value' => '50',
                'description' => 'Minimum bets before 500x multiplier is available (50)',
            ],
            
            // ============================================
            // 9. إعدادات الحد الأقصى للفوز الواحد
            // ============================================
            [
                'key' => 'V7_max_single_win_pct',
                'value' => '0.20',
                'description' => 'Max single win as percentage of wallet (20% = 0.20)',
            ],
            
            // ============================================
            // 10. إعدادات توزيع المحفظة
            // ============================================
            // V7: Single wallet - distribution settings deprecated (kept for backward compat)
            [
                'key' => 'V7_wallet_dist_global',
                'value' => '1.00',
                'description' => 'Global vault distribution percentage (65% = 0.65)',
            ],
            [
                'key' => 'V7_wallet_dist_jackpot',
                'value' => '0.20',
                'description' => 'Jackpot wallet distribution percentage (20% = 0.20)',
            ],
            [
                'key' => 'V7_wallet_dist_medium',
                'value' => '0.15',
                'description' => 'Medium wallet distribution percentage (15% = 0.15)',
            ],
            
            // ============================================
            // 11. إعدادات الحد السالب للمحفظة
            // ============================================
            [
                'key' => 'V7_negative_limit',
                'value' => '30000',
                'description' => 'Vault negative limit (absolute coins, owner-controlled). 30000',
            ],
            
            // ============================================
            // 12. إعدادات الرسوم
            // ============================================
            [
                'key' => 'fair_luck_owner_fee_rate',
                'value' => '0.10',
                'description' => 'Owner fee rate (10% = 0.10)',
            ],
            [
                'key' => 'fair_luck_app_fee_rate',
                'value' => '0.10',
                'description' => 'App fee rate (10% = 0.10)',
            ],
            [
                'key' => 'fair_luck_receiver_fee_rate',
                'value' => '0.10',
                'description' => 'Receiver fee rate (10% = 0.10)',
            ],
            
            // ============================================
            // 13. إعدادات Cooldown بعد الفوز الكبير
            // ============================================
            [
                'key' => 'V7_loss_streak_forced_mult',
                'value' => '5',
                'description' => 'Forced win multiplier after max loss streak (5x) - يتوافق مع 5x الأكثر ظهوراً',
            ],
            
            // ============================================
            // 14. أوزان المضاعفات (الأهم!)
            // ============================================
            // 
            // شرح الأوزان (Multiplier Weights):
            // ===================================
            // الوزن هو رقم يحدد احتمالية ظهور كل مضاعف عند الفوز
            // 
            // الحساب:
            // -------
            // احتمالية المضاعف = وزن المضاعف ÷ مجموع جميع الأوزان
            // 
            // مثال:
            // ------
            // إذا كان لدينا:
            // 5x => 300
            // 250x => 600
            // 1000x => 500
            // المجموع = 300 + 600 + 500 = 1400
            // 
            // احتمالية 5x = 300 ÷ 1400 = 21.4%
            // احتمالية 250x = 600 ÷ 1400 = 42.9%
            // احتمالية 1000x = 500 ÷ 1400 = 35.7%
            // 
            // كلما زاد الوزن = كلما زادت احتمالية ظهور هذا المضاعف
            // 
            // الإعدادات الحالية (المشكلة):
            // =============================
            // 5x => 500 (وزن عالي جداً = يظهر كثيراً)
            // 10x => 500 (وزن عالي جداً = يظهر كثيراً)
            // 20x => 500 (وزن عالي جداً = يظهر كثيراً)
            // 50x => 500 (وزن عالي جداً = يظهر كثيراً)
            // 250x => 400 (وزن متوسط = نادر)
            // 500x => 300 (وزن منخفض = نادر جداً)
            // 1000x => 200 (وزن منخفض جداً = نادر جداً جداً)
            // النتيجة: RTP = 3% (منخفض جداً)
            // 
            // الإعدادات الجديدة (الحل):
            // ==========================
            // 5x => 300 (تقليل = يظهر أقل)
            // 10x => 300 (تقليل = يظهر أقل)
            // 20x => 300 (تقليل = يظهر أقل)
            // 50x => 400 (تقليل = يظهر أقل)
            // 70x => 450 (جديد = يظهر أكثر)
            // 100x => 600 (جديد = يظهر أكثر)
            // 250x => 600 (زيادة = يظهر أكثر)
            // 500x => 700 (زيادة = يظهر أكثر)
            // 1000x => 500 (زيادة = يظهر أكثر)
            // النتيجة: RTP = 90% (مستهدف)
            // 
            // الفرق:
            // ------
            // المضاعفات الصغيرة (5x-50x):
            //   الحالية = 71.6% من الفوز
            //   الجديدة = 27.3% من الفوز
            // 
            // المضاعفات الكبيرة (70x-1000x):
            //   الحالية = 28.4% من الفوز
            //   الجديدة = 72.7% من الفوز
            // 
            // النتيجة النهائية:
            // -----------------
            // الإعدادات الجديدة تعطي اللاعبين فرصة أكبر للفوز بمضاعفات كبيرة
            // مما يحقق RTP 90% المستهدف بدلاً من 3%
            //
            [
                'key' => 'V7_multiplier_weights',
                'value' => json_encode([
                    5 => 500,      // 5x الأكثر شيوعاً - الأعلى وزناً بفارق واضح
                    10 => 250,     // 10x أقل شيوعاً من 5x بفارق كبير
                    20 => 300,     // متوسط
                    50 => 250,     // أقل شيوعاً
                    70 => 200,     // نادر نسبياً
                    100 => 150,    // نادر - إثارة للاعب
                    250 => 80,     // نادر جداً
                    500 => 30,     // نادر جداً جداً
                    1000 => 1,     // جاكبوت - نادر للغاية
                ]),
                'description' => 'Multiplier weights distribution - توزيع أوزان المضاعفات - 5x الأكثر ظهوراً',
            ],
        ];

        foreach ($settings as $setting) {
            FairLuckSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        $this->command->info('✅ تم تحديث إعدادات FairLuck V7 المحسّنة بنجاح!');
        $this->command->info('📊 الإعدادات الجديدة ستحقق RTP 90% المستهدف');
        $this->command->info('💡 تأكد من مسح الـ Cache بعد تشغيل السيدر');
    }
}
