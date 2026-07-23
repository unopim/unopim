<?php

return [
    'configuration' => [
        'publication' => [
            'title'    => 'النشر',
            'info'     => 'طبقة العرض العامة للمحتوى المنشور لكل لغة.',
            'settings' => [
                'title'      => 'إعدادات النشر',
                'enabled'    => 'مفعّل',
                'base-url'   => 'عنوان URL الأساسي',
                'cache-ttl'  => 'مدة التخزين المؤقت (ثوانٍ)',
                'rate-limit' => 'حد المعدل (طلبات/دقيقة)',
                'indexable'  => 'السماح بفهرسة محركات البحث',
            ],
        ],
    ],

    'publications' => [
        'status' => [
            'draft'     => 'مسودة',
            'published' => 'منشور',
            'withdrawn' => 'مسحوب',
            'redacted'  => 'محجوب',
        ],
    ],

    'public' => [
        '404' => [
            'heading' => 'جواز السفر غير موجود.',
        ],
        '429' => [
            'heading' => 'طلبات كثيرة جدًا. يرجى المحاولة مرة أخرى بعد قليل.',
        ],
        'withdrawn' => [
            'heading' => 'هذا الجواز لم يعد متاحًا.',
        ],
    ],
];
