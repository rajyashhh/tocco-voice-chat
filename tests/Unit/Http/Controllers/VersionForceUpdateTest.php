<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\VersionController;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit Test: منطق التحديث الإجباري في VersionController::isForce($version, $OS)
 *
 * خلفية الحادثة: تسبّب منطق الإجبار في قفل جماعي للمستخدمين اليوم. القاعدة الصحيحة:
 *   الإجبار يحدث فقط عندما يكون سويتش "{os}_update_required == 1" *و* version < {os}_min_version.
 *   إذا كان السويتش مطفأ (لا يساوي 1) => لا إجبار إطلاقاً مهما كان رقم الإصدار.
 *
 * الاختبار وحدوي بحت: يستدعي الدالة الخاصة isForce عبر الـ Reflection دون أي HTTP
 * أو قاعدة بيانات. يضبط القيم عبر settings()->set(...) ويعتمد على ملف
 * public/settings.json الحقيقي الذي تستخدمه فئة App\Classes\AppSetting.
 *
 * أمان البيئة: قبل أي شيء نلتقط حالة public/settings.json (هل هو موجود + محتواه
 * كبايتات خام). في tearDown نُعيده بالضبط كما كان — وإن لم يكن موجوداً أصلاً نحذف
 * أي ملف أنشأناه — حتى لا يتلوث/يبقى أي أثر في الريبو. لا قاعدة بيانات ولا إنتاج.
 */
class VersionForceUpdateTest extends TestCase
{
    /** المنصات الثلاث وبادئة المفاتيح المقابلة لكل منها داخل الإعدادات. */
    private const OS_PREFIX = [
        'Android' => 'android',
        'Huawei'  => 'huawei',
        'IOS'     => 'ios',
    ];

    /** مسار ملف الإعدادات الحقيقي الذي تقرأ/تكتب منه فئة AppSetting. */
    private string $settingsPath;

    /** هل كان ملف الإعدادات موجوداً قبل بدء الاختبار؟ */
    private bool $settingsExisted = false;

    /** المحتوى الأصلي الخام للملف (إن وُجد) كي نُعيده حرفياً في النهاية. */
    private ?string $originalSettings = null;

    protected function setUp(): void
    {
        parent::setUp();

        // التقاط حالة الملف الأصلية لحمايته من أي تلوث.
        $this->settingsPath = public_path('settings.json');
        $this->settingsExisted = file_exists($this->settingsPath);
        $this->originalSettings = $this->settingsExisted
            ? file_get_contents($this->settingsPath)
            : null;

        // نبدأ من ملف نظيف معروف الحالة حتى تكون كل حالة اختبار معزولة تماماً،
        // ولا تتسرب قيم من تشغيلات سابقة أو من ملف إنتاجي حقيقي.
        file_put_contents($this->settingsPath, json_encode([]));
    }

    protected function tearDown(): void
    {
        // استعادة الحالة الأصلية بالضبط: نُعيد المحتوى الخام إن كان الملف موجوداً،
        // وإلا نحذف الملف الذي أنشأناه كي لا يبقى أي أثر في الريبو.
        if ($this->settingsExisted) {
            file_put_contents($this->settingsPath, $this->originalSettings);
        } elseif (file_exists($this->settingsPath)) {
            unlink($this->settingsPath);
        }

        parent::tearDown();
    }

    /**
     * استدعاء الدالة الخاصة isForce عبر الـ Reflection.
     */
    private function callIsForce($version, string $os): bool
    {
        $method = new ReflectionMethod(VersionController::class, 'isForce');
        $method->setAccessible(true);

        return (bool) $method->invoke(new VersionController(), $version, $os);
    }

    /**
     * ضبط مفاتيح الإجبار لمنصة معيّنة (السويتش + الحد الأدنى).
     */
    private function configure(string $os, $updateRequired, $minVersion): void
    {
        $prefix = self::OS_PREFIX[$os];
        settings()->set($prefix . '_update_required', $updateRequired);
        settings()->set($prefix . '_min_version', $minVersion);
    }

    /**
     * مزوّد البيانات: تشغيل كل حالة اختبار لكل منصة من المنصات الثلاث.
     */
    public static function osProvider(): array
    {
        return [
            'Android' => ['Android'],
            'Huawei'  => ['Huawei'],
            'IOS'     => ['IOS'],
        ];
    }

    /**
     * (أ) السويتش مطفأ + الإصدار أقل بكثير من الحد الأدنى => لا إجبار.
     * هذا هو سيناريو الحادثة بالضبط: يجب ألا يُقفل أحد عند إطفاء السويتش.
     *
     * @dataProvider osProvider
     */
    public function test_switch_off_with_version_far_below_min_is_not_forced(string $os): void
    {
        $this->configure($os, 0, 100);

        $this->assertFalse(
            $this->callIsForce(1, $os),
            "[$os] عند إطفاء سويتش التحديث الإجباري يجب ألا يُجبَر أي إصدار حتى لو كان أقل بكثير من الحد الأدنى"
        );
    }

    /**
     * (ب) السويتش مفعّل + الإصدار أقل من الحد الأدنى => إجبار.
     *
     * @dataProvider osProvider
     */
    public function test_switch_on_with_version_below_min_is_forced(string $os): void
    {
        $this->configure($os, 1, 100);

        $this->assertTrue(
            $this->callIsForce(99, $os),
            "[$os] عند تفعيل السويتش وإصدار أقل من الحد الأدنى يجب أن يُجبَر التحديث"
        );
    }

    /**
     * (ج) السويتش مفعّل + الإصدار أكبر من الحد الأدنى => لا إجبار.
     *
     * @dataProvider osProvider
     */
    public function test_switch_on_with_version_above_min_is_not_forced(string $os): void
    {
        $this->configure($os, 1, 100);

        $this->assertFalse(
            $this->callIsForce(150, $os),
            "[$os] الإصدار الأحدث من الحد الأدنى يجب ألا يُجبَر حتى مع تفعيل السويتش"
        );
    }

    /**
     * (د) الحالة الحدّية: الإصدار == الحد الأدنى => لا إجبار (الشرط < صارم).
     *
     * @dataProvider osProvider
     */
    public function test_switch_on_with_version_equal_to_min_is_not_forced(string $os): void
    {
        $this->configure($os, 1, 100);

        $this->assertFalse(
            $this->callIsForce(100, $os),
            "[$os] الإصدار المساوي تماماً للحد الأدنى يجب ألا يُجبَر لأن الشرط أقل-من صارم"
        );
    }

    /**
     * عزل المنصات: تفعيل الإجبار على Android يجب ألا يؤثر على Huawei أو IOS،
     * فكل منصة تقرأ مفاتيحها الخاصة فقط. يحمي من تكرار حادثة القفل الجماعي عبر منصة واحدة.
     */
    public function test_force_on_one_os_does_not_affect_other_os(): void
    {
        // تفعيل الإجبار على Android فقط، مع إبقاء بقية المنصات بسويتش مطفأ.
        $this->configure('Android', 1, 100);
        $this->configure('Huawei', 0, 100);
        $this->configure('IOS', 0, 100);

        $this->assertTrue(
            $this->callIsForce(99, 'Android'),
            'Android المفعّل يجب أن يُجبِر الإصدار الأقل'
        );
        $this->assertFalse(
            $this->callIsForce(99, 'Huawei'),
            'Huawei بسويتش مطفأ يجب ألا يتأثر بتفعيل Android'
        );
        $this->assertFalse(
            $this->callIsForce(99, 'IOS'),
            'IOS بسويتش مطفأ يجب ألا يتأثر بتفعيل Android'
        );
    }
}
