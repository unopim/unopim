<?php

return [
    'importers' => [
        'products' => [
            'title'      => 'منتجات',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'                        => 'مفتاح URL: تم إنشاء \'%s\' بالفعل لعنصر يحتوي على SKU: \'%s\'.',
                    'invalid-attribute-family'                 => 'قيمة غير صالحة لعمود عائلة السمات (عائلة السمات غير موجودة؟)',
                    'invalid-type'                             => 'نوع المنتج غير صالح أو غير مدعوم',
                    'sku-not-found'                            => 'لم يتم العثور على المنتج الذي يحتوي على SKU محدد',
                    'super-attribute-not-found'                => 'السمة القابلة للتكوين مع الكود :code لم يتم العثور عليها أو لا تنتمي إلى عائلة السمات :familyCode',
                    'configurable-attributes-not-found'        => 'السمات القابلة للتكوين مطلوبة لإنشاء نموذج المنتج',
                    'configurable-attributes-wrong-type'       => 'يُسمح فقط لسمات النوع المحددة التي لا تعتمد على اللغة أو القناة بأن تكون سمات قابلة للتكوين لمنتج قابل للتكوين',
                    'variant-configurable-attribute-not-found' => 'السمة المتغيرة القابلة للتكوين :code مطلوبة للإنشاء',
                    'not-unique-variant-product'               => 'يوجد منتج بنفس السمات القابلة للتكوين بالفعل.',
                    'channel-not-exist'                        => 'هذه القناة غير موجودة.',
                    'locale-not-in-channel'                    => 'لم يتم تحديد هذه اللغة في القناة.',
                    'locale-not-exist'                         => 'هذه اللغة غير موجودة',
                    'not-unique-value'                         => 'يجب أن تكون قيمة :code فريدة.',
                    'incorrect-family-for-variant'             => 'يجب أن تكون العائلة هي نفس عائلة الوالدين',
                    'parent-not-exist'                         => 'الوالد غير موجود.',
                ],
            ],
        ],
        'categories' => [
            'title'      => 'فئات',
            'validation' => [
                'errors' => [
                    'channel-related-category-root' => 'لا يمكنك حذف الفئة الجذرية المرتبطة بالقناة',
                ],
            ],
        ],
        'attributes' => [
            'title'      => 'السمات',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'كود السمة :code مستخدم بالفعل.',
                    'code_not_found_to_delete'             => 'لم يتم العثور على كود السمة للحذف.',
                    'code_is_system_and_cannot_be_deleted' => 'لا يمكن حذف سمة النظام.',
                ],
            ],
        ],
        'attribute-groups' => [
            'title'      => 'مجموعات السمات',
            'validation' => [
                'errors' => [
                    'duplicate-code'                       => 'كود مجموعة السمات :code مستخدم بالفعل.',
                    'code_not_found_to_delete'             => 'لم يتم العثور على كود مجموعة السمات للحذف.',
                    'code_is_system_and_cannot_be_deleted' => 'لا يمكن حذف مجموعة سمات النظام.',
                ],
            ],
        ],
        'attribute-families' => [
            'title'      => 'عائلات السمات',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'كود عائلة السمات :code مستخدم بالفعل.',
                    'code_not_found_to_delete' => 'لم يتم العثور على كود عائلة السمات للحذف.',
                    'invalid-attribute-group'  => 'مجموعة السمات ":code" غير موجودة.',
                    'invalid-attribute'        => 'السمة ":code" غير موجودة.',
                    'invalid-channel'          => 'القناة ":code" غير موجودة.',
                ],
            ],
        ],
        'attribute-options' => [
            'title'      => 'خيارات السمة',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'كود خيار السمة :code مستخدم بالفعل.',
                    'code_not_found_to_delete' => 'لم يتم العثور على كود خيار السمة للحذف.',
                    'locale-not-exist'         => 'اللغة المحلية ":code" غير موجودة.',
                    'invalid-attribute'        => 'السمة ":code" غير موجودة.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'products' => [
            'title'      => 'منتجات',
            'validation' => [
                'errors' => [
                    'duplicate-url-key'         => 'مفتاح URL: تم إنشاء \'%s\' بالفعل لعنصر يحتوي على SKU: \'%s\'.',
                    'invalid-attribute-family'  => 'قيمة غير صالحة لعمود عائلة السمات (عائلة السمات غير موجودة؟)',
                    'invalid-type'              => 'نوع المنتج غير صالح أو غير مدعوم',
                    'sku-not-found'             => 'لم يتم العثور على المنتج الذي يحتوي على SKU محدد',
                    'super-attribute-not-found' => 'السمة المميزة ذات الرمز: \'%s\' لم يتم العثور عليها أو لا تنتمي إلى عائلة السمات: \'%s\'',
                ],
            ],
        ],
        'categories' => [
            'title' => 'فئات',
        ],
        'attributes' => [
            'title' => 'السمات',
        ],
        'attribute-groups' => [
            'title' => 'مجموعات السمات',
        ],
        'attribute-families' => [
            'title' => 'عائلات السمات',
        ],
        'attribute-options' => [
            'title' => 'خيارات السمة',
        ],
    ],
    'validation' => [
        'errors' => [
            'column-empty-headers' => 'تحتوي الأعمدة رقم "%s" على رؤوس فارغة.',
            'column-name-invalid'  => 'أسماء الأعمدة غير صالحة: "%s".',
            'column-not-found'     => 'لم يتم العثور على الأعمدة المطلوبة: %s.',
            'column-numbers'       => 'عدد الأعمدة لا يتوافق مع عدد الصفوف في الرأس.',
            'invalid-attribute'    => 'يحتوي الرأس على سمة (سمات) غير صالحة: "%s".',
            'system'               => 'حدث خطأ غير متوقع في النظام.',
            'wrong-quotes'         => 'يتم استخدام علامات الاقتباس المتعرجة بدلاً من علامات الاقتباس المستقيمة.',
            'file-empty'           => 'The file is empty or does not contain a header row. Please upload a valid file with data.',
        ],
    ],
    'job' => [
        'started'   => 'بدأ تنفيذ المهمة',
        'completed' => 'الانتهاء من تنفيذ المهمة',
    ],
];
