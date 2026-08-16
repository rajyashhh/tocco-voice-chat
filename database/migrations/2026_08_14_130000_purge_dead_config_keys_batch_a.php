<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-approved cleanup (2026-08-14), batch A: 16 dead `configs` keys.
 *
 * Every key was verified to have zero live consumers before deletion:
 *  - twilio_* rows carried REAL credentials in the DB (Common::sendSMS reads
 *    them, but Twilio is being rebuilt by another dev; rows go, code stays).
 *  - min_tx_num / f_yj_ratio / union_share / no_family_ratio / is_family_ratio
 *    are only read by MoneyTrait::tixian / getFeeRatio / getUnionFeeRatio /
 *    Common::gmOrderDataFormat — none of which has a single caller.
 *  - register_account: the LIVE path reads the settings table via
 *    Common::getSettingValue; this removes only the stale configs copy.
 *  - cost_request_backround is the TYPO row; the real key
 *    cost_request_background stays untouched.
 *  - storage_base_url / cp_xssm / default_img / boss_id / cp: only referenced
 *    from commented-out code, lang files, or dormant legacy seeders.
 *
 * Bulk delete deliberately goes through the query builder — bulk queries do
 * not fire Eloquent events, so ConfigObserver will NOT flush all_configs.
 * The flush is therefore done explicitly here.
 */
return new class extends Migration
{
    private const KEYS = [
        'twilio_sid',
        'twilio_api_key',
        'twilio_from',
        'twilio_service',
        'storage_base_url',
        'min_tx_num',
        'cp_xssm',
        'default_img',
        'boss_id',
        'register_account',
        'cost_request_backround',
        'f_yj_ratio',
        'union_share',
        'no_family_ratio',
        'is_family_ratio',
        'cp',
    ];

    /**
     * Safe restore values for down(). Secrets are intentionally NOT restored
     * (Twilio rows come back empty); the rest mirror the legacy seeder
     * defaults so a rollback leaves the panel in a working state.
     */
    private const RESTORE = [
        'twilio_sid'             => ['value' => '',              'desc' => null],
        'twilio_api_key'         => ['value' => '',              'desc' => null],
        'twilio_from'            => ['value' => '',              'desc' => null],
        'twilio_service'         => ['value' => '',              'desc' => null],
        'storage_base_url'       => ['value' => '',              'desc' => null],
        'min_tx_num'             => ['value' => '1000',          'desc' => 'الحد الادنى للسحب لليوزر العادي'],
        'cp_xssm'                => ['value' => '7',             'desc' => null],
        'default_img'            => ['value' => '/images/1.png', 'desc' => 'الصورة الافتراضية'],
        'boss_id'                => ['value' => '1912',          'desc' => 'ايدي الرئيسي'],
        'register_account'       => ['value' => '3',             'desc' => 'حد الحسابات المسموح بتسجيلها من نفس الجهاز'],
        'cost_request_backround' => ['value' => '2000',          'desc' => null],
        'f_yj_ratio'             => ['value' => '10',            'desc' => null],
        'union_share'            => ['value' => '10',            'desc' => null],
        'no_family_ratio'        => ['value' => '10',            'desc' => null],
        'is_family_ratio'        => ['value' => '20',            'desc' => null],
        'cp'                     => ['value' => '',              'desc' => null],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('configs')) {
            return;
        }

        DB::table('configs')->whereIn('name', self::KEYS)->delete();

        $this->flushConfigCache();
    }

    public function down(): void
    {
        if (!Schema::hasTable('configs')) {
            return;
        }

        foreach (self::RESTORE as $name => $row) {
            $exists = DB::table('configs')->where('name', $name)->exists();
            if ($exists) {
                continue;
            }

            DB::table('configs')->insert([
                'name'       => $name,
                'value'      => $row['value'],
                'desc'       => $row['desc'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->flushConfigCache();
    }

    private function flushConfigCache(): void
    {
        // Cache backend may be absent while migrating in hermetic/build
        // contexts; the flush is best-effort there, runtime deploys have Redis.
        try {
            Cache::forget('all_configs');
            foreach (self::KEYS as $name) {
                Cache::forget($name);
            }
        } catch (\Throwable $e) {
            // no cache available — nothing stale to flush
        }
    }
};
