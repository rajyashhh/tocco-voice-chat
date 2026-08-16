# Installation & Setup Guide 🛠️

دليل التثبيت والإعداد لـ Moment Viewer

## المتطلبات الأساسية 📋

### Backend Requirements
- ✅ PHP 7.4 or higher
- ✅ Laravel 8.x or higher
- ✅ Laravel-Admin Package
- ✅ MySQL 5.7 or higher

### Frontend Requirements
- ✅ jQuery 3.6+
- ✅ SweetAlert2 11+
- ✅ Font Awesome 6.4+

### Database Tables Required
- `moment` - جدول الـ Moments
- `moment_user_likes` - جدول اللايكات
- `moment_user_comments` - جدول التعليقات
- `moment_user_gifts` - جدول الهدايا
- `moment_gallery` - جدول الصور/الفيديوهات

## خطوات التثبيت 🚀

### الخطوة 1: التأكد من الملفات

تأكد من وجود جميع الملفات التالية:

```
Modules/Moment/
├── Http/Controllers/web/
│   └── MomentViewerController.php
├── Resources/views/viewer/
│   └── index.blade.php
├── Config/
│   └── viewer.php
└── Routes/
    └── web.php (محدث)
```

### الخطوة 2: تحديث composer.json (اختياري)

إذا كنت تريد إضافة dependencies جديدة:

```bash
cd /path/to/project
composer update
```

### الخطوة 3: Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### الخطوة 4: تحقق من الصلاحيات

تأكد من أن middleware `moment.allowed` مُعرّف في:

```
app/Http/Middleware/
```

إذا لم يكن موجوداً، تحقق من:
```
app/Http/Kernel.php
```

### الخطوة 5: Test الروابط

```bash
php artisan route:list | grep moment-viewer
```

يجب أن تظهر جميع الروابط التالية:
```
GET|HEAD  admin/moment-viewer ........................ admin.moment-viewer.index
GET|HEAD  admin/moment-viewer/api/moment/{id} ...... admin.moment-viewer.api.moment
GET|HEAD  admin/moment-viewer/api/moment/{id}/comments admin.moment-viewer.api.comments
GET|HEAD  admin/moment-viewer/api/moment/{id}/gifts admin.moment-viewer.api.gifts
GET|HEAD  admin/moment-viewer/api/moment/{id}/likes admin.moment-viewer.api.likes
GET|HEAD  admin/moment-viewer/api/moments .......... admin.moment-viewer.api.moments
DELETE    admin/moment-viewer/api/comment/{id} .... admin.moment-viewer.api.delete-comment
DELETE    admin/moment-viewer/api/moment/{id} ..... admin.moment-viewer.api.delete-moment
POST      admin/moment-viewer/api/reset-random .... admin.moment-viewer.api.reset-random
```

## التكوين ⚙️

### 1. تحديث Environment Variables

أضف في `.env`:

```env
# Moment Viewer Configuration
MOMENT_VIEWER_PER_PAGE=12
MOMENT_VIEWER_DEFAULT_SORT=random
```

### 2. نشر ملف Configuration

```bash
php artisan vendor:publish --tag=moment-config
```

أو يمكنك تحرير الملف مباشرة:
```
Modules/Moment/Config/viewer.php
```

### 3. الإعدادات المتاحة

```php
// عدد العناصر في الصفحة
'per_page' => 12,

// الترتيب الافتراضي
'default_sort' => 'random', // random, newest, oldest

// تمكين/تعطيل الميزات
'features' => [
    'likes' => true,
    'comments' => true,
    'gifts' => true,
    'delete_moment' => true,
    'delete_comment' => true,
],
```

## التحقق من التثبيت ✅

### 1. اختبار الوصول

افتح المتصفح وتوجه إلى:
```
https://your-domain.com/admin/moment-viewer
```

### 2. اختبار API

استخدم Postman أو cURL:

```bash
# Test Get Moments
curl -X GET "https://your-domain.com/admin/moment-viewer/api/moments?sort=random" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test Get Moment Details
curl -X GET "https://your-domain.com/admin/moment-viewer/api/moment/1" \
  -H "Accept: application/json"
```

### 3. اختبار الترتيب

1. جرب كل خيارات الترتيب
2. تأكد من تحديث القائمة
3. تحقق من Pagination

### 4. اختبار التفاعلات

1. افتح moment
2. جرب كل tabs (Comments, Likes, Gifts)
3. جرب حذف تعليق
4. جرب حذف moment

## استكشاف الأخطاء 🔧

### المشكلة: الصفحة لا تفتح (404)

**الحل**:
```bash
php artisan route:clear
php artisan cache:clear
```

### المشكلة: الصور لا تظهر

**الحل**:
تأكد من:
1. Helper function `getImagePath()` موجودة
2. مسار الصور صحيح
3. الصلاحيات على مجلد `storage`

```bash
php artisan storage:link
chmod -R 755 storage/
```

### المشكلة: CSRF Token Mismatch

**الحل**:
```bash
php artisan config:clear
php artisan cache:clear
```

تأكد من وجود:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### المشكلة: Middleware Error

**الحل**:
تحقق من أن middleware `moment.allowed` مُعرّف:

```php
// في app/Http/Kernel.php
protected $routeMiddleware = [
    // ...
    'moment.allowed' => \App\Http\Middleware\MomentAllowed::class,
];
```

### المشكلة: Database Query Error

**الحل**:
تحقق من:
1. جداول قاعدة البيانات موجودة
2. Relationships في Models صحيحة
3. Foreign Keys سليمة

```bash
php artisan migrate
php artisan db:seed
```

### المشكلة: JavaScript Errors

**الحل**:
1. افتح Console في المتصفح (F12)
2. تحقق من الأخطاء
3. تأكد من تحميل jQuery و SweetAlert2

```html
<!-- في head أو قبل </body> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
```

## الصلاحيات 🔐

### إضافة صلاحيات في Laravel-Admin

```php
// في Database Seeder أو Console
\Encore\Admin\Auth\Database\Permission::create([
    'name' => 'Moment Viewer',
    'slug' => 'moment.viewer',
    'http_method' => ['GET', 'POST', 'DELETE'],
    'http_path' => [
        'moment-viewer*',
    ],
]);
```

### ربط الصلاحيات بالـ Role

```php
// في Laravel-Admin
$role = \Encore\Admin\Auth\Database\Role::find(1);
$permission = \Encore\Admin\Auth\Database\Permission::where('slug', 'moment.viewer')->first();
$role->permissions()->attach($permission);
```

## الأداء والتحسين ⚡

### 1. Database Indexing

أضف indexes للأداء الأفضل:

```sql
-- في migration file
$table->index('user_id');
$table->index('created_at');
$table->index(['user_id', 'created_at']);
```

### 2. Caching (اختياري)

إذا كنت تريد تفعيل الكاش:

```php
// في Config/viewer.php
'cache' => [
    'enabled' => true,
    'ttl' => 300, // 5 minutes
],
```

### 3. Image Optimization

استخدم package للصور:

```bash
composer require intervention/image
```

## التكامل مع التطبيق 📱

### API توحيد

إذا كنت تريد توحيد API مع التطبيق:

```php
// في routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('moment-viewer')->group(function () {
        Route::get('/moments', [MomentViewerController::class, 'getMoments']);
        // ... إلخ
    });
});
```

## النسخ الاحتياطي 💾

### قبل التحديثات

```bash
# Backup Database
mysqldump -u username -p database_name > backup.sql

# Backup Files
tar -czf moment_backup.tar.gz Modules/Moment/
```

### استعادة النسخة الاحتياطية

```bash
# Restore Database
mysql -u username -p database_name < backup.sql

# Restore Files
tar -xzf moment_backup.tar.gz
```

## الدعم 💬

إذا واجهت أي مشاكل:

1. راجع هذا الدليل
2. تحقق من Log Files:
   ```
   storage/logs/laravel.log
   ```
3. افتح Console في المتصفح
4. تواصل مع فريق التطوير

## الخلاصة ✨

بعد إتمام هذه الخطوات، يجب أن يكون Moment Viewer:
- ✅ مُثبت بشكل صحيح
- ✅ يعمل بدون أخطاء
- ✅ جميع الميزات فعّالة
- ✅ الأداء محسّن

استمتع باستخدام Moment Viewer! 🎉

---

**آخر تحديث**: 2026-01-08  
**النسخة**: 1.0.0
