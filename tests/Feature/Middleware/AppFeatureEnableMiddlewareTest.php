<?php

namespace Tests\Feature\Middleware;

use App\Models\AppFeature;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature Test: App\Http\Middleware\AppFeatureEnable (appFeatureEnable:{slug})
 *
 * الخلفية: جسم الـmiddleware كان معلّقاً بالكامل (no-op) فكانت راوتات الموبايل
 * المحمية (lucky/pk/families/mall/agencies + Modules) لا تُقفل فعلياً عند إطفاء
 * السويتش من اللوحة. بعد التفعيل، العقد المطلوب:
 *
 *   - slug status=1 أو غير موجود في الجدول => مرور طبيعي (default allow —
 *     حرج لأن 5 slugs تُحذف من الجدول بالتوازي: game, chat, cp, room_target,
 *     salary_transaction).
 *   - slug status=0 => على مسار api/*: رد Common::apiResponse المتسق
 *     (success=false, message مترجمة, data=null, HTTP 403). على مسار غير api:
 *     abort(403).
 *   - القراءة عبر AppFeatureService::isEnable (كاش rememberForever بمفتاح
 *     app_feature_status_{slug}) لا استعلام مباشر — مسار ساخن.
 *   - قلب الـstatus من اللوحة يمسح الكاش فوراً عبر AppFeature::saved/deleted
 *     hooks فيتأثر الراوت من الطلب التالي مباشرة.
 *
 * الاختبار يسجّل راوتات مؤقتة خاصة به (api/qa-feature-gate/... و web) حتى لا
 * يعتمد على auth:sanctum أو بيانات مستخدمين. قاعدة البيانات داخل transaction
 * تُرجع كما كانت، والكاش array يُمسح في setUp.
 */
class AppFeatureEnableMiddlewareTest extends TestCase
{
    use DatabaseTransactions;

    /** slug اختباري لن يصطدم بأي صف حقيقي. */
    private const SLUG = 'qa_gate_slug';

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // راوت API محمي بالـmiddleware — يحاكي راوتات الموبايل المحمية.
        Route::middleware('appFeatureEnable:' . self::SLUG)
            ->get('/api/qa-feature-gate/protected', fn () => response()->json(['ok' => true]));

        // راوت API بلا middleware — يجب ألا يتأثر بأي سويتش.
        Route::get('/api/qa-feature-gate/open', fn () => response()->json(['ok' => true]));

        // راوت ويب محمي — يغطي فرع abort(403) غير الـAPI.
        Route::middleware('appFeatureEnable:' . self::SLUG)
            ->get('/qa-feature-gate/web', fn () => response('ok'));
    }

    protected function tearDown(): void
    {
        // الكاش array لا يعبر بين التستات، لكن ننظف احتياطاً لمفتاح السويتش.
        Cache::forget('app_feature_status_' . self::SLUG);

        parent::tearDown();
    }

    private function createFeature(int $status): AppFeature
    {
        return AppFeature::create([
            'name'    => 'QA Gate',
            'name_ar' => 'بوابة QA',
            'slug'    => self::SLUG,
            'status'  => $status,
        ]);
    }

    /** (1) slug مفعّل (status=1) => الراوت يعدي بسلوكه الطبيعي. */
    public function test_enabled_slug_passes_through(): void
    {
        $this->createFeature(1);

        $this->getJson('/api/qa-feature-gate/protected')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    /** (2) slug مقفول (status=0) => 403 برد API المتسق (success/message/data). */
    public function test_disabled_slug_returns_consistent_403_api_response(): void
    {
        $this->createFeature(0);

        $this->getJson('/api/qa-feature-gate/protected')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => __('This feature has not been activated for you'),
                'data'    => null,
            ]);
    }

    /** (3) slug غير موجود في الجدول إطلاقاً => مرور طبيعي (default allow). */
    public function test_missing_slug_defaults_to_allow(): void
    {
        $this->assertDatabaseMissing('app_features', ['slug' => self::SLUG]);

        $this->getJson('/api/qa-feature-gate/protected')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    /**
     * (3-ب) الـ5 slugs التي تُحذف من الجدول بالتوازي يجب أن تعدي default allow —
     * حماية من كسر راوتات موجودة أثناء شغل الزميل على app_features_cards.
     */
    public function test_slugs_scheduled_for_deletion_default_to_allow(): void
    {
        foreach (['game', 'chat', 'cp', 'room_target', 'salary_transaction'] as $slug) {
            AppFeature::where('slug', $slug)->delete();
            Cache::forget("app_feature_status_{$slug}");

            Route::middleware("appFeatureEnable:{$slug}")
                ->get("/api/qa-feature-gate/deleted-{$slug}", fn () => response()->json(['ok' => true]));

            $this->getJson("/api/qa-feature-gate/deleted-{$slug}")
                ->assertStatus(200)
                ->assertJson(['ok' => true]);
        }
    }

    /**
     * (4) مسح الكاش عبر AppFeature::saved hook: القلبة تأثر من الطلب التالي فوراً
     * في الاتجاهين (تفعيل -> قفل -> تفعيل) رغم rememberForever.
     */
    public function test_status_flip_takes_effect_immediately_via_saved_hook(): void
    {
        $feature = $this->createFeature(1);

        // طلب أول يملأ الكاش بقيمة "مفعّل".
        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(200);
        $this->assertTrue(Cache::has('app_feature_status_' . self::SLUG));

        // قفل من اللوحة => saved hook يمسح المفتاح => الطلب التالي 403 فوراً.
        $feature->update(['status' => 0]);
        $this->assertFalse(
            Cache::has('app_feature_status_' . self::SLUG),
            'saved hook يجب أن يمسح كاش السويتش فور تعديل status'
        );
        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(403);

        // إعادة التفعيل => يفتح فوراً بنفس الآلية.
        $feature->update(['status' => 1]);
        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(200);
    }

    /** (4-ب) حذف الصف نفسه (deleted hook) يرجع السلوك لـdefault allow فوراً. */
    public function test_row_deletion_restores_default_allow_immediately(): void
    {
        $feature = $this->createFeature(0);

        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(403);

        $feature->delete();

        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(200);
    }

    /** (5) راوت بلا middleware لا يتأثر حتى والسويتش مقفول. */
    public function test_route_without_middleware_is_unaffected(): void
    {
        $this->createFeature(0);

        $this->getJson('/api/qa-feature-gate/open')
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    /** (6) المسار الساخن: الطلب الثاني يقرأ من الكاش بلا أي استعلام على app_features. */
    public function test_second_request_hits_cache_not_database(): void
    {
        $this->createFeature(1);

        // الطلب الأول يملأ الكاش.
        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(200);

        DB::enableQueryLog();
        $this->getJson('/api/qa-feature-gate/protected')->assertStatus(200);
        $queries = collect(DB::getQueryLog())->pluck('query');
        DB::disableQueryLog();

        $this->assertFalse(
            $queries->contains(fn ($sql) => str_contains($sql, 'app_features')),
            'الطلب الثاني يجب أن يُخدم من الكاش بدون أي استعلام app_features. القائمة: ' . $queries->implode(' | ')
        );
    }

    /** (7) فرع غير الـAPI: راوت ويب مقفول => 403 (abort) وليس رد JSON. */
    public function test_web_route_disabled_slug_aborts_403(): void
    {
        $this->createFeature(0);

        $this->get('/qa-feature-gate/web')->assertStatus(403);
    }
}
