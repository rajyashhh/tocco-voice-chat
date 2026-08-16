# كيفية تشغيل Seeder لإنشاء بيانات تجريبية 🌱

## الخطوة 1: تشغيل الـ Seeder

افتح Terminal واكتب الأمر التالي:

```bash
php artisan db:seed --class=Modules\\Moment\\Database\\Seeders\\MomentViewerTestSeeder
```

أو يمكنك استخدام:

```bash
cd <backend-repo>
php artisan db:seed --class="Modules\Moment\Database\Seeders\MomentViewerTestSeeder"
```

## ما سيتم إنشاؤه؟

الـ Seeder سيقوم بإنشاء:
- ✅ **30 Moment** تجريبية
- ✅ **1-5 صور** لكل Moment
- ✅ **0-15 لايك** عشوائية لكل Moment
- ✅ **0-10 تعليقات** عشوائية لكل Moment
- ✅ **0-5 هدايا** عشوائية لبعض الـ Moments

## متطلبات التشغيل

تأكد من:
1. ✅ وجود مستخدمين في قاعدة البيانات
2. ✅ جداول قاعدة البيانات جاهزة:
   - `moment`
   - `moment_user_likes`
   - `moment_user_comments`
   - `moment_user_gifts`
   - `moment_gallery`

## إذا لم يكن هناك مستخدمين

قم بإنشاء مستخدمين أولاً:

```bash
php artisan db:seed --class=UsersTableSeeder
```

أو أنشئ مستخدم واحد على الأقل من لوحة التحكم.

## التحقق من النتيجة

بعد تشغيل الـ Seeder:

1. افتح المتصفح
2. توجه إلى: `/admin/moment-viewer`
3. يجب أن ترى 30 moment مع بيانات تجريبية

## حذف البيانات التجريبية (اختياري)

إذا أردت حذف البيانات التجريبية:

```sql
-- تحذير: سيحذف جميع الـ Moments!
TRUNCATE TABLE moment_user_likes;
TRUNCATE TABLE moment_user_comments;
TRUNCATE TABLE moment_user_gifts;
TRUNCATE TABLE moment_gallery;
TRUNCATE TABLE moment;
```

## إعادة التشغيل

يمكنك تشغيل الـ Seeder عدة مرات لإضافة المزيد من البيانات التجريبية.

---

**ملاحظة**: الصور في البيانات التجريبية هي مسارات وهمية. يمكنك استبدالها بصور حقيقية لاحقاً.
