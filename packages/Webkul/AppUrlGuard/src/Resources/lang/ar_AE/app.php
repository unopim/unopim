<?php

return [
    'warning' => [
        'title'           => 'تم اكتشاف عدم تطابق في APP_URL',
        'dismiss'         => 'تجاهل',
        'lede-before'     => 'أصول الواجهة الأمامية لديك (CSS وJS) مثبتة على القيمة المُهيأة',
        'lede-after'      => 'قم بتحديثها لتطابق المضيف الذي تستخدمه، وإلا فلن يتم تحميل الأنماط والنصوص البرمجية.',
        'configured-env'  => 'المُهيأ (.env)',
        'mismatch-tag'    => 'عدم تطابق',
        'actual-browser'  => 'الفعلي (المتصفح)',
        'in-use-tag'      => 'قيد الاستخدام',
        'toggle-step'     => 'تبديل الخطوة :number',
        'step-1-title'    => 'قم بتحديث APP_URL في ملف .env الخاص بك',
        'step-1-hint'     => 'افتح ملف .env الخاص بالمشروع واستبدل سطر APP_URL.',
        'step-2-title'    => 'امسح ذاكرة التخزين المؤقت للتطبيق',
        'step-2-hint'     => 'قم بتشغيل هذا في الطرفية من جذر المشروع.',
        'copy'            => 'نسخ',
        'copied'          => 'تم النسخ',
        'note-bold'       => 'ثم قم بتحديث الصفحة بالكامل',
        'note-rest'       => 'حتى يعيد المتصفح تحميل الأصول المُحدَّثة.',
        'progress'        => 'اكتملت :done من :total خطوات',
        'all-done'        => 'تم كل شيء',
        'powered-by'      => 'مدعوم بواسطة',
        'open-source-by'  => 'مشروع مفتوح المصدر من',
        'copied-toast'    => 'تم النسخ إلى الحافظة',
        'still-mismatch'  => 'لا يزال APP_URL غير متطابق. قم بتحديث .env وتشغيل "php artisan optimize:clear".',
        'verify-failed'   => 'تعذر التحقق من APP_URL. يرجى تحديث الصفحة.',
        'logged-out'      => 'تم تسجيل الخروج: APP_URL لا يطابق المضيف الحالي. قم بتحديث APP_URL في .env وتشغيل "php artisan optimize:clear".',
    ],

    'log' => [
        'mismatch' => 'تم اكتشاف عدم تطابق في APP_URL',
        'hint'     => 'قم بتحديث APP_URL في ملف .env ليطابق عنوان URL للطلب، ثم نفّذ: php artisan optimize:clear',
    ],
];
