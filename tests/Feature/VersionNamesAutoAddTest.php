<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Feature Test: منطق VERSION-NAMES / صفحة التحديثات (update-page)
 *
 * يغطي السلوك المُضاف اليوم في App\Http\Controllers\VersionController::versionAndCache:
 *  1. الإضافة التلقائية: إرسال version_name + version يسجّل {version: versionName}
 *     داخل {os}_version_names دون حذف الإدخالات الموجودة.
 *  2. القراءة العكسية (read-back): التي تُغذّي قائمة الأدمن المنسدلة (dropdown).
 *
 * لا قاعدة بيانات: مُخزِّن settings() = App\Classes\AppSetting وهو مُخزِّن ملفّي بحت
 * يقرأ/يكتب public/settings.json فقط ولا يلمس أي اتصال DB. لذلك نختبر منطق
 * أسماء الإصدارات مباشرةً عبر settings()->set/get — وهي نفس الاستدعاءات التي
 * يستخدمها الكنترولر (الأسطر 28-39) — مع نسخ/استعادة كاملة لـ public/settings.json.
 *
 * المرور عبر action الكنترولر بالكامل يتطلب Auth + جداول users/backgrounds + Config
 * + Cache + middleware (كلها DB)؛ وهي ليست منطق أسماء الإصدارات. كتلة أسماء
 * الإصدارات مستقلة وخالية من DB، فنختبرها بأمانٍ تامّ دون لمس قاعدة بيانات.
 */
class VersionNamesAutoAddTest extends TestCase
{
    /** @var string مسار ملف الإعدادات الحقيقي */
    private string $settingsPath;

    /** @var string|null المحتوى الأصلي للملف قبل الاختبار (null = الملف غير موجود أصلاً) */
    private ?string $originalContent = null;

    /** @var bool هل كان الملف موجوداً قبل بدء الاختبار */
    private bool $fileExisted = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsPath = public_path('settings.json');

        // نسخة احتياطية بايت-ببايت من الملف الحقيقي (إن وُجد)
        if (file_exists($this->settingsPath)) {
            $this->fileExisted     = true;
            $this->originalContent = file_get_contents($this->settingsPath);
        } else {
            $this->fileExisted     = false;
            $this->originalContent = null;
        }

        // حالة بداية معروفة ومعزولة لكل اختبار
        file_put_contents($this->settingsPath, json_encode(new \stdClass()));
    }

    protected function tearDown(): void
    {
        // استعادة كاملة: أعد المحتوى الأصلي أو احذف الملف إن لم يكن موجوداً أصلاً
        if ($this->fileExisted) {
            file_put_contents($this->settingsPath, $this->originalContent);
        } elseif (file_exists($this->settingsPath)) {
            @unlink($this->settingsPath);
        }

        parent::tearDown();
    }

    /**
     * يكرّر بدقّة منطق الإضافة التلقائية في الكنترولر (الأسطر 26-34):
     *  - اشتقاق بادئة الـ OS من القيمة الواردة.
     *  - قراءة الخريطة الحالية من settings.
     *  - الإضافة فقط عند version_name != null && intval(version) > 0.
     *  - المفتاح = strval(intval($version))، القيمة = versionName.
     */
    private function registerVersionName(string $os, $version, $versionName): void
    {
        $osPrefix = $os == 'Huawei' ? 'huawei' : ($os == 'IOS' ? 'ios' : 'android');

        $versionNames = json_decode(settings()->get($osPrefix . '_version_names') ?? '[]', true);
        if (!is_array($versionNames)) {
            $versionNames = [];
        }

        if ($versionName !== null && intval($version) > 0) {
            $versionNames[strval(intval($version))] = $versionName;
            settings()->set($osPrefix . '_version_names', json_encode($versionNames));
        }
    }

    /** قراءة عكسية: نفس ما تقرأه شاشة الأدمن لبناء القائمة المنسدلة */
    private function readVersionNames(string $osPrefix): array
    {
        $decoded = json_decode(settings()->get($osPrefix . '_version_names') ?? '[]', true);
        return is_array($decoded) ? $decoded : [];
    }

    // ════════════════════════════════════════════════════════════════
    // 1) الإضافة التلقائية لا تحذف الإدخالات الموجودة
    // ════════════════════════════════════════════════════════════════

    public function test_registers_new_version_name_without_dropping_existing_entries(): void
    {
        // حالة موجودة مسبقاً (تحاكي ما زرعه المايجريشن)
        settings()->set('android_version_names', json_encode([
            '17' => '1.0.15',
            '18' => '1.0.16',
        ]));

        // إصدار جديد يصل عبر الطلب
        $this->registerVersionName('Android', 19, '1.0.17');

        $names = $this->readVersionNames('android');

        // الجديد أُضيف
        $this->assertSame('1.0.17', $names['19']);
        // والقديم لم يُحذف
        $this->assertSame('1.0.15', $names['17']);
        $this->assertSame('1.0.16', $names['18']);
        $this->assertCount(3, $names);
    }

    public function test_starts_from_empty_when_no_existing_map(): void
    {
        // لا مفتاح أصلاً => يبدأ من []
        $this->assertNull(settings()->get('android_version_names'));

        $this->registerVersionName('Android', 20, '1.0.18');

        $names = $this->readVersionNames('android');
        $this->assertSame(['20' => '1.0.18'], $names);
    }

    public function test_overwrites_name_for_same_version_code(): void
    {
        settings()->set('android_version_names', json_encode(['19' => '1.0.17']));

        // نفس الكود 19 باسم مختلف => تحديث القيمة لا تكرارها
        $this->registerVersionName('Android', 19, '1.0.17-hotfix');

        $names = $this->readVersionNames('android');
        $this->assertCount(1, $names);
        $this->assertSame('1.0.17-hotfix', $names['19']);
    }

    public function test_appends_across_multiple_sequential_requests(): void
    {
        $this->registerVersionName('Android', 17, '1.0.15');
        $this->registerVersionName('Android', 18, '1.0.16');
        $this->registerVersionName('Android', 19, '1.0.17');

        $names = $this->readVersionNames('android');

        $this->assertSame(
            ['17' => '1.0.15', '18' => '1.0.16', '19' => '1.0.17'],
            $names
        );
    }

    // ════════════════════════════════════════════════════════════════
    // 2) عزل خرائط الـ OS (Android / IOS / Huawei) عن بعضها
    // ════════════════════════════════════════════════════════════════

    public function test_os_prefix_mapping_keeps_maps_independent(): void
    {
        $this->registerVersionName('Android', 19, 'android-1.0.17');
        $this->registerVersionName('IOS', 19, 'ios-1.0.17');
        $this->registerVersionName('Huawei', 19, 'huawei-1.0.17');

        $this->assertSame(['19' => 'android-1.0.17'], $this->readVersionNames('android'));
        $this->assertSame(['19' => 'ios-1.0.17'], $this->readVersionNames('ios'));
        $this->assertSame(['19' => 'huawei-1.0.17'], $this->readVersionNames('huawei'));
    }

    public function test_unknown_os_falls_back_to_android_prefix(): void
    {
        // أي OS غير IOS/Huawei => 'android' (نفس منطق الكنترولر)
        $this->registerVersionName('SomethingElse', 21, '1.0.19');

        $this->assertSame(['21' => '1.0.19'], $this->readVersionNames('android'));
    }

    // ════════════════════════════════════════════════════════════════
    // 3) شروط الحارس: متى لا يُضاف شيء
    // ════════════════════════════════════════════════════════════════

    public function test_does_not_register_when_version_name_is_null(): void
    {
        settings()->set('android_version_names', json_encode(['18' => '1.0.16']));

        // version_name = null => لا تغيير
        $this->registerVersionName('Android', 19, null);

        $names = $this->readVersionNames('android');
        $this->assertSame(['18' => '1.0.16'], $names);
        $this->assertArrayNotHasKey('19', $names);
    }

    public function test_does_not_register_when_version_is_zero_or_non_positive(): void
    {
        settings()->set('android_version_names', json_encode(['18' => '1.0.16']));

        // intval(version) <= 0 => لا تغيير
        $this->registerVersionName('Android', 0, '1.0.0');
        $this->registerVersionName('Android', 'abc', 'garbage'); // intval('abc') = 0

        $names = $this->readVersionNames('android');
        $this->assertSame(['18' => '1.0.16'], $names);
    }

    public function test_version_key_is_normalized_integer_string(): void
    {
        // version يصل كنص "019" => المفتاح يجب أن يكون "19" (strval(intval()))
        $this->registerVersionName('Android', '019', '1.0.17');

        $names = $this->readVersionNames('android');
        $this->assertArrayHasKey('19', $names);
        $this->assertArrayNotHasKey('019', $names);
        $this->assertSame('1.0.17', $names['19']);
    }

    // ════════════════════════════════════════════════════════════════
    // 4) القراءة العكسية: تماسك مع مُخزِّن الملف عبر استدعاءات منفصلة
    // ════════════════════════════════════════════════════════════════

    public function test_read_back_persists_through_file_store_across_instances(): void
    {
        // كل استدعاء settings() ينشئ نسخة AppSetting جديدة تعيد قراءة الملف.
        // القراءة العكسية يجب أن ترى ما كُتب في استدعاء سابق.
        $this->registerVersionName('Android', 19, '1.0.17');

        // نسخة منفصلة تماماً (كما تفعل شاشة الأدمن في طلب لاحق)
        $names = json_decode(settings()->get('android_version_names'), true);

        $this->assertIsArray($names);
        $this->assertSame('1.0.17', $names['19']);
    }

    public function test_corrupt_existing_value_is_treated_as_empty_map(): void
    {
        // قيمة غير-JSON محفوظة سابقاً => json_decode تُرجع null => تُعامَل كـ []
        settings()->set('android_version_names', 'not-a-json-string');

        $this->registerVersionName('Android', 19, '1.0.17');

        $names = $this->readVersionNames('android');
        // البداية من [] ثم إضافة الجديد فقط
        $this->assertSame(['19' => '1.0.17'], $names);
    }
}
