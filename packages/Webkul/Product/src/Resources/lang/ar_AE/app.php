<?php

return [
    'acl' => [
        'variant-structures' => 'بنى المتغيرات',
    ],

    'datagrid' => [
        'copy-of-slug'                  => 'نسخة من-:value',
        'copy-of'                       => 'نسخة من :value',
        'variant-already-exist-message' => 'يوجد بالفعل متغير بنفس خيارات السمات.',
    ],

    'response' => [
        'product-can-not-be-copied' => 'منتجات من النوع :type لا يمكن نسخها',
    ],

    'type'     => [
        'configurable'  => 'قابلة للتكوين',
        'simple'        => 'بسيطة',
        'variant-group' => 'مجموعة المتغيرات',
        'variant_group' => 'مجموعة المتغيرات',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => 'تم اكتشاف مرجع دائري في القالب: :path.',
            'currency-not-allowed'    => 'سمات السعر فقط هي التي تقبل عملة، لكن ":attribute" ليست سمة سعر.',
            'currency-not-enabled'    => 'العملة ":currency" المستخدمة في القالب غير مفعلة لأي قناة.',
            'currency-not-in-channel' => 'العملة ":currency" غير مفعلة للقناة ":channel".',
            'filter-failed'           => 'تعذر تطبيق عامل التصفية ":filter" على قيمة ":attribute".',
            'filter-not-supported'    => 'لا يمكن تطبيق عامل التصفية ":filter" على السمة ":attribute".',
            'format-date-position'    => 'يجب أن يكون عامل التصفية format_date هو عامل التصفية الأول في ":expression".',
            'inside-html-tag'         => 'لا يمكن وضع تعبيرات القالب داخل وسوم HTML أو سماتها مثل الروابط والصور.',
            'invalid-filter-argument' => 'عامل التصفية ":filter" في ":expression" يحتوي على وسيطة مفقودة أو غير صالحة.',
            'invalid-reference'       => 'القالب المخزن في ":attribute" غير صالح وتعذر عرضه.',
            'invalid-syntax'          => 'تعبير القالب ":expression" غير صالح. استخدم رمز سمة متبوعًا بعوامل تصفية اختيارية، على سبيل المثال: name | upper.',
            'max-depth'               => 'مراجع القالب متداخلة بعمق يزيد عن :max مستويات عند ":attribute".',
            'missing-value'           => 'السمة ":attribute" ليس لها قيمة لهذه القناة واللغة.',
            'not-in-family'           => 'السمة ":attribute" المستخدمة في القالب لا تنتمي إلى عائلة المنتج.',
            'output-too-large'        => 'القالب المعروض أكبر من :max بايت وتم اقتطاعه.',
            'self-reference'          => 'لا يمكن للقالب الإشارة إلى سمته الخاصة ":attribute".',
            'single-brace'            => 'تم العثور على ":text" بين أقواس معقوفة مفردة. تتطلب تعبيرات القالب أقواسًا معقوفة مزدوجة.',
            'source-too-long'         => 'يمكن أن يبلغ طول القالب :max بايت كحد أقصى.',
            'too-complex'             => 'القالب معقد جدًا بحيث يتعذر التحقق منه. اختصره أو بسّطه.',
            'too-many-expressions'    => 'يمكن أن يحتوي القالب على :max تعبيرات كحد أقصى.',
            'too-many-filters'        => 'يمكن لتعبير القالب ":expression" استخدام :max عوامل تصفية كحد أقصى.',
            'unclosed-expression'     => 'تم فتح تعبير قالب ولكن لم يتم إغلاقه.',
            'unknown-attribute'       => 'السمة ":attribute" المستخدمة في القالب غير موجودة.',
            'unknown-currency'        => 'العملة ":currency" المستخدمة في القالب غير موجودة.',
            'unknown-filter'          => 'عامل التصفية ":filter" غير مدعوم. عوامل التصفية المتاحة: :filters.',
            'unsupported-attribute'   => 'لا يمكن استخدام السمة ":attribute" في قالب لأن نوعها غير مدعوم.',
        ],
    ],
];
