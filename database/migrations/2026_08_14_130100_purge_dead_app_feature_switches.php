<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-approved cleanup (2026-08-14): 5 dead app_features switches.
 *
 * Each slug was grepped across app/, Modules/ and routes/ — zero live
 * consumers: no `appFeatureEnable:{slug}` route middleware, no
 * AppFeatureService::isEnable()/validateStatusEnable() call reads any of
 * them. Their only references are the dormant AppFeatureSeeder and the
 * display-only IN_APP_SLUGS partition list in the admin cards page (which
 * simply stops rendering a card once the row is gone).
 *
 * Bulk delete via query builder skips Eloquent events, so the AppFeature
 * model's deleted() cache hook won't run — app_feature_status_{slug} is
 * flushed explicitly (AppFeatureService caches rememberForever).
 */
return new class extends Migration
{
    /**
     * name/name_ar mirror the live table rows so down() restores them
     * exactly as they were, enabled (status=1) like the originals.
     */
    private const FEATURES = [
        'game'               => ['name' => 'Game Feature',               'name_ar' => 'ميزه الالعاب'],
        'chat'               => ['name' => 'Chat Feature',               'name_ar' => 'ميزه الدردشه'],
        'cp'                 => ['name' => 'Cp Feature',                 'name_ar' => 'ميزه ال cp'],
        'room_target'        => ['name' => 'Room Target Feature',        'name_ar' => 'ميزه ال Room Target'],
        'salary_transaction' => ['name' => 'Salary Transaction Feature', 'name_ar' => 'ميزه تحويل الرواتب'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('app_features')) {
            return;
        }

        $slugs = array_keys(self::FEATURES);

        DB::table('app_features')->whereIn('slug', $slugs)->delete();

        $this->flushFeatureCache($slugs);
    }

    public function down(): void
    {
        if (!Schema::hasTable('app_features')) {
            return;
        }

        foreach (self::FEATURES as $slug => $names) {
            $exists = DB::table('app_features')->where('slug', $slug)->exists();
            if ($exists) {
                continue;
            }

            DB::table('app_features')->insert([
                'name'       => $names['name'],
                'name_ar'    => $names['name_ar'],
                'slug'       => $slug,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->flushFeatureCache(array_keys(self::FEATURES));
    }

    private function flushFeatureCache(array $slugs): void
    {
        try {
            foreach ($slugs as $slug) {
                Cache::forget("app_feature_status_{$slug}");
            }
        } catch (\Throwable $e) {
            // no cache backend during hermetic builds — nothing to flush
        }
    }
};