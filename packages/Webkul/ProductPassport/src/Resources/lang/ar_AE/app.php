<?php

return [
    'type' => [
        'label' => 'جواز المنتج الرقمي',
    ],

    'configuration' => [
        'product_passport' => [
            'title'    => 'جواز المنتج',
            'info'     => 'إعدادات نشر جواز المنتج الرقمي.',
            'settings' => [
                'title'                  => 'إعدادات جواز المنتج',
                'enabled'                => 'مفعّل',
                'auto-publish'           => 'النشر التلقائي عند الحفظ',
                'completeness-threshold' => 'حد اكتمال البيانات (%)',
                'operator-name'          => 'اسم المشغل الاقتصادي',
                'operator-address'       => 'عنوان المشغل الاقتصادي',
                'operator-eu-rep'        => 'الممثل المعتمد في الاتحاد الأوروبي',
                'support-url'            => 'رابط الدعم',
            ],
        ],
    ],
];
