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
        'category-fields' => [
            'title'      => 'حقول الفئة',
            'validation' => [
                'errors' => [
                    'duplicate-code'           => 'رمز حقل الفئة :code مستخدم بالفعل.',
                    'code_not_found_to_delete' => 'لم يتم العثور على رمز حقل الفئة للحذف.',
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
        'locales' => [
            'title'      => 'اللغات',
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'رمز اللغة \'%s\' تم استيراده بالفعل في هذه الدفعة.',
                    'code-not-found-to-delete'    => 'لم يتم العثور على لغة بالرمز \'%s\' في النظام.',
                    'invalid-status'              => 'يجب أن تكون الحالة 0 أو 1 (أو فارغة للتفعيل الافتراضي).',
                    'channel-related-locale-root' => 'لا يمكنك حذف اللغة بالرمز :code لأنها مرتبطة بقناة.',
                ],
            ],
        ],
        'channels' => [
            'title'      => 'القنوات',
            'validation' => [
                'errors' => [
                    'code-not-found-to-delete' => 'القناة ذات الرمز :code غير موجودة للحذف.',
                    'locale-not-found'         => 'واحد أو أكثر من اللغات غير موجود.',
                    'root-category-not-found'  => 'الفئة الجذرية غير موجودة.',
                    'currency-not-found'       => 'واحدة أو أكثر من العملات غير موجودة.',
                    'invalid-locale'           => 'اللغة غير موجودة.',
                ],
            ],
        ],
        'currencies' => [
            'title'   => 'Currencies',
            'filters' => [
                'status' => 'الحالة',
                'enable' => 'مفعل',
                'all'    => 'الكل',
            ],
            'validation' => [
                'errors' => [
                    'duplicate-code'              => 'Currency code \'%s\' was already imported in this batch.',
                    'code-not-found-to-delete'    => 'Currency with code \'%s\' not found in the system.',
                    'invalid-status'              => 'Status must be 0 or 1 (or empty for default enabled).',
                    'channel-related-locale-root' => 'You cannot delete the locale with code :code because it is associated with a channel.',
                ],
            ],
        ],
        'roles' => [
            'title'      => 'Roles',
            'validation' => [
                'errors' => [
                    'duplicate-name'           => 'Duplicate role name found.',
                    'name-not-found-to-delete' => 'Role with the specified name not found to delete.',
                ],
            ],
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'الحالة',
                'active' => 'نشط',
                'all'    => 'الكل',
            ],
            'validation' => [
                'errors' => [
                    'email-not-found-to-delete' => 'User with specified email not found to delete.',
                    'invalid-role'              => 'Invalid role name found.',
                    'invalid-locale'            => 'Invalid UI locale code found.',
                ],
            ],
        ],
    ],
    'exporters' => [
        'export-too-large' => 'هذا التصدير كبير جدًا بحيث لا يمكن تشغيله: العدد التقديري :rows صف × :columns عمود (~:estimated) يتجاوز المساحة المتاحة (~:available). قلّص نطاق التصدير باختيار عدد أقل من القنوات/اللغات (والسمات) ثم حاول مرة أخرى.',
        'fields'           => [
            'file-format'         => 'تنسيق الملف',
            'with-media'          => 'مع الوسائط',
            'header-row'          => 'Header Row',
            'header-row-info'     => 'Write attribute codes as the first line',
            'use-labels'          => 'Use Labels',
            'use-labels-info'     => 'Export readable labels instead of codes',
            'date-format'         => 'Date Format',
            'date-format-options' => [
                'yyyy-mm-dd'       => 'YYYY-MM-DD',
                'dd-mm-yyyy'       => 'DD-MM-YYYY',
                'dd-mm-yyyy-slash' => 'DD/MM/YYYY',
                'mm-dd-yyyy-slash' => 'MM/DD/YYYY',
            ],
            'file-path'      => 'File Path',
            'file-path-info' => 'File name pattern. Tokens: [code], [date], [time], [entity_type]',
            'status'         => 'الحالة',
            'enable'         => 'مفعّل',
            'all'            => 'الكل',
        ],
        'products' => [
            'title'              => 'منتجات',
            'invalid-locales'    => 'ليست كل اللغات المحددة متاحة للقنوات المحددة.',
            'invalid-currencies' => 'ليست كل العملات المحددة متاحة للقنوات المحددة.',
            'filters'            => [
                'channels'             => 'القنوات',
                'channels-info'        => 'Values are exported for each selected channel\'s scope. Leave empty to export every channel.',
                'currencies'           => 'العملات',
                'currencies-info'      => 'يتم تصدير سمات السعر لكل عملة محددة. اتركه فارغًا لتصدير جميع عملات القناة.',
                'locales'              => 'اللغات',
                'locales-info'         => 'يتم تصدير السمات القابلة للترجمة مرة واحدة لكل لغة محددة. اتركه فارغًا لتصدير جميع لغات القناة.',
                'attributes'           => 'السمات',
                'attributes-info'      => 'يتم تصدير السمات المحددة فقط. اتركه فارغًا لتصدير جميع السمات في العائلة.',
                'attribute-families'   => 'عائلات السمات',
                'categories'           => 'الفئات',
                'completeness'         => 'الاكتمال',
                'completeness-options' => [
                    'none'         => 'لا يوجد شرط على الاكتمال',
                    'at-least-one' => 'مكتمل في لغة واحدة على الأقل من اللغات المحددة',
                    'all'          => 'مكتمل في جميع اللغات المحددة',
                ],
                'time-condition' => 'شرط الوقت',
                'time-options'   => [
                    'none'              => 'لا يوجد شرط على التاريخ',
                    'last-n-days'       => 'المنتجات المحدّثة خلال آخر N أيام',
                    'between-dates'     => 'المنتجات المحدّثة بين تاريخين',
                    'since-last-export' => 'المنتجات المحدّثة منذ آخر تصدير',
                ],
                'time-value'     => 'عدد الأيام',
                'time-date'      => 'تاريخ البدء',
                'time-date-end'  => 'تاريخ الانتهاء',
                'status'         => 'الحالة',
                'status-options' => [
                    'enable'  => 'مفعّل',
                    'disable' => 'معطّل',
                    'all'     => 'الكل',
                ],
                'sku'              => 'SKU',
                'sku-info'         => 'Comma separated SKUs to export, e.g. SKU001, SKU002, SKU003. Leave empty to export every product.',
                'identifiers'      => 'المعرّفات',
                'identifiers-info' => 'الصق معرّف SKU / معرّفًا واحدًا في كل سطر لتصدير تلك المنتجات فقط. اتركه فارغًا لتصدير جميع المنتجات.',
            ],
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
        'category-fields' => [
            'title' => 'حقول الفئة',
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
        'locales' => [
            'title' => 'اللغات',
        ],
        'channels' => [
            'title' => 'القنوات',
        ],
        'currencies' => [
            'title' => 'Currencies',
        ],
        'roles' => [
            'title' => 'Roles',
        ],
        'users' => [
            'title'   => 'Users',
            'filters' => [
                'status' => 'Status',
                'active' => 'Active',
                'all'    => 'All',
            ],
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
