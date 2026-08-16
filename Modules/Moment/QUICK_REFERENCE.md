# ⚡ Quick Reference - Moment Feed Viewer

## 🚀 الوصول السريع
```
URL: /admin/moment-viewer
```

## 🎯 المميزات الأساسية
1. ✅ تصميم Facebook Feed (680px عمودي)
2. ✅ بحث بالمستخدم (Name/UUID)
3. ✅ تعديل الوصف (Edit Description)
4. ✅ حذف البوستات والتعليقات
5. ✅ عرض Likes, Comments, Gifts inline
6. ✅ معرض الصور مع التنقل

## 📊 البيانات التجريبية
```bash
# إنشاء 30 moment تجريبي
php artisan db:seed --class=Modules\\Moment\\Database\\Seeders\\MomentViewerTestSeeder
```

## 🔑 الأكشنز المتاحة

### على البوست
- **⋮ Menu** → Edit / Delete
- **📸 Gallery** → أسهم للتنقل
- **💬 Comments** → عرض/إخفاء التعليقات
- **❤️ Likes** → عرض/إخفاء المعجبين
- **🎁 Gifts** → عرض الهدايا في popup

### على التعليق
- **🗑️ Delete** → حذف التعليق

## 🎨 التصميم
- Background: `#f0f2f5`
- Primary: `#1877f2` (Facebook Blue)
- Feed Width: `680px`
- Border Radius: `8px`

## 🔧 API Endpoints الأساسية
```
GET  /admin/moment-viewer/api/moments?search=...&sort=...
GET  /admin/moment-viewer/api/moment/{id}/comments
GET  /admin/moment-viewer/api/moment/{id}/likes
PUT  /admin/moment-viewer/api/moment/{id}/description
DELETE /admin/moment-viewer/api/moment/{id}
DELETE /admin/moment-viewer/api/comment/{id}
```

## 💡 نصائح سريعة
1. استخدم خانة البحث للفلترة بالمستخدم
2. اضغط على الأزرار لعرض التفاعلات inline
3. القائمة ⋮ تحتوي على Edit و Delete
4. كل العمليات بدون refresh للصفحة

## 🐛 حل سريع للمشاكل
```bash
# لا تظهر بيانات؟
php artisan db:seed --class=Modules\\Moment\\Database\\Seeders\\MomentViewerTestSeeder

# مشكلة في الصور؟
php artisan storage:link

# تحقق من permissions
php artisan cache:clear
```

## 📁 الملفات الرئيسية
```
Controller: Modules/Moment/Http/Controllers/web/MomentViewerController.php
View:       Modules/Moment/Resources/views/viewer/index.blade.php
Routes:     Modules/Moment/Routes/web.php
Seeder:     Modules/Moment/Database/Seeders/MomentViewerTestSeeder.php
```

## ✅ Checklist التشغيل
- [x] Seeder executed (30 moments)
- [x] Routes configured (10 routes)
- [x] View designed (Facebook-style)
- [x] API endpoints working
- [x] Search functionality
- [x] Edit/Delete operations
- [x] Inline interactions
- [x] Documentation complete

---
**للتفاصيل الكاملة**: راجع [FEED_VIEWER_GUIDE.md](FEED_VIEWER_GUIDE.md)
