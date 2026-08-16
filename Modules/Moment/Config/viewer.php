<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Moment Viewer Configuration
    |--------------------------------------------------------------------------
    |
    | هنا يمكنك تخصيص إعدادات Moment Viewer
    |
    */

    // عدد الـ Moments في كل صفحة
    'per_page' => env('MOMENT_VIEWER_PER_PAGE', 12),

    // الترتيب الافتراضي
    'default_sort' => env('MOMENT_VIEWER_DEFAULT_SORT', 'random'), // random, newest, oldest

    // تمكين/تعطيل الميزات
    'features' => [
        'likes' => true,
        'comments' => true,
        'gifts' => true,
        'delete_moment' => true,
        'delete_comment' => true,
        'navigation' => true,
    ],

    // إعدادات الميديا
    'media' => [
        'max_display_items' => 10, // أقصى عدد للصور/الفيديوهات المعروضة
        'default_avatar' => 'images/businessman-icon.jpg',
    ],

    // إعدادات Pagination
    'pagination' => [
        'per_page_options' => [12, 24, 36, 48],
        'show_page_info' => true,
    ],

    // إعدادات Random Sorting
    'random' => [
        'session_based' => true, // الحفاظ على نفس الترتيب في الجلسة
        'seed_lifetime' => 3600, // مدة صلاحية الـ seed بالثواني
    ],

    // إعدادات الكاش (مستقبلية)
    'cache' => [
        'enabled' => false,
        'ttl' => 300, // 5 minutes
    ],
];
