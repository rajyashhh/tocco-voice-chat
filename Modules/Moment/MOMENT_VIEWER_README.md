# Moment Viewer - Facebook-Style Experience 🎨

## نظرة عامة
واجهة عرض Moments حديثة ومشابهة لتجربة Facebook، مصممة خصيصاً للداشبورد الإداري مع تحكم كامل بالمحتوى.

## المميزات الرئيسية ✨

### 1️⃣ واجهة عرض حديثة
- تصميم مشابه لـ Facebook/Instagram Stories
- عرض شبكي responsive للـ Moments
- بطاقات تفاعلية مع معاينة الصور والفيديو
- واجهة نظيفة وسهلة الاستخدام

### 2️⃣ خيارات الترتيب المتعددة
- **ترتيب عشوائي (Random)**: يحافظ على نفس الترتيب خلال الجلسة
- **الأحدث أولاً (Newest)**: ترتيب تنازلي حسب تاريخ النشر
- **الأقدم أولاً (Oldest)**: ترتيب تصاعدي حسب تاريخ النشر

### 3️⃣ التفاعلات الكاملة

#### اللايكات (Likes)
- عرض عدد اللايكات
- قائمة المستخدمين الذين قاموا بعمل Like
- معلومات المستخدم (الاسم، UUID، الصورة)

#### التعليقات (Comments)
- عرض جميع التعليقات
- حذف التعليق من قبل الأدمن مع تأكيد
- عرض تاريخ ووقت التعليق

#### الهدايا (Gifts)
- عرض جميع الهدايا المرسلة
- ترتيب حسب (الأحدث / الأعلى قيمة)
- عرض صورة الهدية وقيمتها

### 4️⃣ معلومات المنشئ
- صورة المستخدم
- اسم المستخدم
- User ID & UUID
- رابط مباشر لصفحة المستخدم

### 5️⃣ إدارة المحتوى
- حذف Moment مع تأكيد
- حذف تعليق مع تأكيد
- تحديث تلقائي بدون reload

## الملفات المضافة 📁

```
Modules/Moment/
├── Http/Controllers/web/
│   └── MomentViewerController.php      # الكنترولر الجديد
├── Resources/views/viewer/
│   └── index.blade.php                 # صفحة العرض الرئيسية
└── Routes/
    └── web.php                         # الروابط المحدثة
```

## الروابط (Routes) 🔗

### الرابط الرئيسي
```
GET /admin/moment-viewer
```

### API Endpoints
```php
GET  /admin/moment-viewer/api/moments              # جلب قائمة Moments
GET  /admin/moment-viewer/api/moment/{id}          # تفاصيل Moment
GET  /admin/moment-viewer/api/moment/{id}/likes    # قائمة اللايكات
GET  /admin/moment-viewer/api/moment/{id}/comments # قائمة التعليقات
GET  /admin/moment-viewer/api/moment/{id}/gifts    # قائمة الهدايا
DELETE /admin/moment-viewer/api/comment/{id}       # حذف تعليق
DELETE /admin/moment-viewer/api/moment/{id}        # حذف Moment
POST /admin/moment-viewer/api/reset-random         # إعادة تعيين الترتيب العشوائي
```

## المعاملات (Parameters) ⚙️

### جلب Moments
```javascript
{
  sort: 'random|newest|oldest',  // نوع الترتيب
  page: 1,                       // رقم الصفحة
  per_page: 12                   // عدد العناصر في الصفحة
}
```

### جلب الهدايا
```javascript
{
  sort: 'newest|highest_value'   // ترتيب الهدايا
}
```

## الاستخدام 💻

### 1. الوصول للصفحة
```
https://your-domain.com/admin/moment-viewer
```

### 2. تغيير الترتيب
- استخدم القائمة المنسدلة في الأعلى
- يتم تحديث القائمة تلقائياً بدون reload

### 3. عرض تفاصيل Moment
- اضغط على أي Moment Card
- يفتح Modal يعرض:
  - الصور/الفيديوهات بشكل كامل
  - معلومات المنشئ
  - اللايكات والتعليقات والهدايا

### 4. حذف محتوى
- زر Delete في كل Moment Card
- زر Delete بجوار كل تعليق
- تأكيد قبل الحذف

## التصميم والـ UI 🎨

### الألوان الرئيسية
```css
Primary: #1877f2 (Facebook Blue)
Background: #f0f2f5
Text: #1c1e21
Secondary Text: #65676b
Danger: #e41e3f
Warning: #f7b928
```

### التجاوب (Responsive)
- Desktop: Grid 3-4 columns
- Tablet: Grid 2 columns
- Mobile: Grid 1 column

### الأنيميشن
- Smooth transitions
- Hover effects
- Loading spinners
- Modal animations

## الأمان والصلاحيات 🔒

### Middleware المطبق
```php
'moment.allowed'   // التحقق من صلاحية الوصول لـ Moments
'admin'            // التحقق من أن المستخدم أدمن
'adminIp'          // فلترة IP
'multiLanguage'    // دعم اللغات المتعددة
```

### CSRF Protection
جميع عمليات POST/DELETE محمية بـ CSRF Token

## الأداء والتحسينات ⚡

### 1. Session-Based Random
```php
// الترتيب العشوائي يحافظ على نفس النتيجة في نفس الجلسة
$seed = $request->session()->get('moment_random_seed');
```

### 2. Pagination
- عرض 12 moment في كل صفحة
- تنقل سهل بين الصفحات
- معلومات واضحة عن الصفحة الحالية

### 3. Eager Loading
```php
// تحميل العلاقات مسبقاً لتقليل استعلامات قاعدة البيانات
->with(['user', 'images', 'likes', 'comments'])
->withCount(['likes', 'comments'])
```

### 4. AJAX بدون Reload
- جميع العمليات تتم عبر AJAX
- تحديث سلس بدون إعادة تحميل الصفحة

## المتطلبات 📋

### Frontend Libraries
- jQuery 3.6+
- SweetAlert2 11+
- Font Awesome 6.4+
- Google Fonts (Inter)

### Backend Requirements
- Laravel 8+
- PHP 7.4+
- Laravel-Admin Package

## التخصيص 🛠

### تغيير عدد العناصر في الصفحة
في `MomentViewerController.php`:
```php
$perPage = $request->get('per_page', 12); // غير 12 للعدد المطلوب
```

### تغيير الألوان
في `index.blade.php` ابحث عن:
```css
:root {
    --primary-color: #1877f2;
    --background-color: #f0f2f5;
    /* ... إلخ */
}
```

### إضافة فلاتر إضافية
في `getMoments()` method:
```php
// مثال: فلتر حسب المستخدم
if ($userId = $request->get('user_id')) {
    $query->where('user_id', $userId);
}
```

## المشاكل الشائعة والحلول 🔧

### 1. الصور لا تظهر
**الحل**: تأكد من أن helper function `getImagePath()` موجودة

### 2. الترتيب العشوائي لا يتغير
**الحل**: استخدم زر "Refresh" أو اضغط على reset random من API

### 3. صلاحيات الوصول
**الحل**: تأكد من أن middleware `moment.allowed` مُعرّف

### 4. CSRF Token Missing
**الحل**: تأكد من وجود meta tag في head:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

## الاختبار 🧪

### 1. اختبار العرض الأساسي
```bash
# افتح الرابط
/admin/moment-viewer
```

### 2. اختبار الترتيب
- جرب كل خيارات الترتيب
- تأكد من تحديث القائمة

### 3. اختبار التفاعلات
- افتح moment
- جرب كل tabs (Comments, Likes, Gifts)
- جرب حذف تعليق

### 4. اختبار الحذف
- احذف moment
- تأكد من التحديث التلقائي

## الدعم والتطوير المستقبلي 🚀

### تحسينات مقترحة
- [ ] إضافة فلتر بحث متقدم
- [ ] دعم الإشعارات الفورية
- [ ] تصدير البيانات (Export)
- [ ] إحصائيات متقدمة
- [ ] دعم Dark Mode

### التواصل
للمساعدة أو الإبلاغ عن مشاكل، تواصل مع فريق التطوير.

---

**تم التطوير بواسطة**: GitHub Copilot  
**التاريخ**: January 8, 2026  
**النسخة**: 1.0.0
