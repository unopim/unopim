<?php

return [
    'config' => [
        'completeness' => [
            'title'           => 'الاكتمال',
            'info'            => 'عتبات ألوان درجة الاكتمال.',
            'green-from'      => 'أخضر من (%)',
            'green-from-info' => 'تُعرض النتائج عند هذه النسبة أو أعلى باللون الأخضر.',
            'red-below'       => 'أحمر أقل من (%)',
            'red-below-info'  => 'تُعرض النتائج الأقل من هذه النسبة باللون الأحمر، وما بينها باللون الكهرماني.',
            'green-gt-red'    => 'يجب أن تكون عتبة الأخضر أكبر من عتبة الأحمر.',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'completeness' => 'الاكتمال',
            ],
        ],
    ],
    'catalog' => [
        'families' => [
            'edit' => [
                'completeness' => [
                    'update-success'      => 'تم تحديث الاكتمال بنجاح',
                    'title'               => 'الاكتمال',
                    'configure'           => 'تكوين الاكتمال',
                    'channel-required'    => 'مطلوب في القنوات',
                    'save-btn'            => 'حفظ',
                    'back-btn'            => 'رجوع',
                    'mass-update-success' => 'تم تحديث الاكتمال بنجاح',
                    'datagrid'            => [
                        'code'             => 'الرمز',
                        'name'             => 'الاسم',
                        'channel-required' => 'مطلوب في القنوات',
                        'actions'          => [
                            'change-requirement' => 'تغيير متطلبات الاكتمال',
                        ],
                    ],
                ],
            ],
        ],
        'products' => [
            'index' => [
                'datagrid' => [
                    'missing-completeness-setting' => 'غير متوفر',
                    'completeness'                 => 'مكتمل',
                ],
            ],
            'edit' => [
                'completeness' => [
                    'title'    => 'الاكتمال',
                    'subtitle' => 'متوسط الاكتمال',
                ],
                'required-attributes' => 'سمات مطلوبة مفقودة',
            ],
        ],
    ],
    'notifications' => [
        'completeness-title'             => 'اكتمل حساب الاكتمال',
        'completeness-calculated'        => 'تم حساب الاكتمال لـ :count منتج.',
        'completeness-calculated-family' => 'تم حساب الاكتمال لـ :count منتج في العائلة ":family".',
        'email-subject'                  => 'اكتمل حساب الاكتمال',
        'email-greeting'                 => 'مرحباً،',
        'email-body'                     => 'تم إكمال حساب الاكتمال لـ :count منتج.',
        'email-body-family'              => 'تم إكمال حساب الاكتمال لـ :count منتج في عائلة السمات ":family".',
        'email-footer'                   => 'يمكنك عرض تفاصيل الاكتمال على لوحة التحكم الخاصة بك.',
    ],
    'dashboard' => [
        'index' => [
            'completeness' => [
                'calculated-products' => 'المنتجات المحسوبة',
                'suggestion'          => [
                    'low'     => 'اكتمال منخفض، أضف تفاصيل للتحسين.',
                    'medium'  => 'استمر، واصل إضافة المعلومات.',
                    'high'    => 'شبه مكتمل، بقيت بعض التفاصيل.',
                    'perfect' => 'معلومات المنتج مكتملة بالكامل.',
                ],
            ],
        ],
    ],
];
