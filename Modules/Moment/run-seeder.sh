#!/bin/bash

# Moment Viewer Test Data Seeder Script
# هذا السكريبت يقوم بإنشاء بيانات تجريبية لاختبار Moment Viewer

echo "================================================"
echo "  Moment Viewer - Test Data Seeder"
echo "================================================"
echo ""

# التحقق من وجود artisan
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found!"
    echo "Please run this script from the Laravel root directory."
    exit 1
fi

echo "🌱 Starting seeder..."
echo ""

# تشغيل الـ Seeder
php artisan db:seed --class=Modules\\Moment\\Database\\Seeders\\MomentViewerTestSeeder

echo ""
echo "================================================"
echo "✅ Seeder completed!"
echo ""
echo "📱 You can now visit:"
echo "   https://your-domain.com/admin/moment-viewer"
echo ""
echo "================================================"
