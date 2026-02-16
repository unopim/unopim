<?php

return [
    'exporters' => [
        'shopify' => [
            'product'  => 'منتج شوبفاي',
            'category' => 'فئة شوبفاي',
        ],
    ],

    'importers' => [
        'shopify' => [
            'product'   => 'منتج شوبيفاي',
            'category'  => 'فئة شوبيفاي',
            'attribute' => 'خاصية شوبيفاي',
            'family'    => 'عائلة شوبيفاي',
            'metafield' => 'تعريفات الحقول الوصفية في شوبيفاي',
        ],
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'shopify'         => 'شوبفاي',
                'credentials'     => 'بيانات الاعتماد',
                'export-mappings' => 'تعيينات التصدير',
                'import-mappings' => 'استيراد التعيينات',
                'settings'        => 'الإعدادات',
            ],
        ],
    ],

    'shopify' => [
        'acl' => [
            'credential' => [
                'create' => 'إنشاء',
                'edit'   => 'تحرير',
                'delete' => 'حذف',
            ],
            'metafield'  => [
                'create'      => 'إنشاء ميتافيلد',
                'edit'        => 'تحرير ميتافيلد',
                'delete'      => 'حذف ميتافيلد',
                'mass_delete' => 'الحذف الجماعي لميتافيلد',
            ],
        ],

        'version' => 'الإصدار: 1.0.0',

        'credential' => [
            'export' => [
                'locales' => 'تعيين اللغات',
            ],
            'shopify' => [
                'locale' => 'لغة شوبفاي',
            ],
            'unopim' => [
                'locale' => 'لغة يونوبيم',
            ],
            'delete-success' => 'تم حذف بيانات الاعتماد بنجاح',
            'created'        => 'تم إنشاء بيانات الاعتماد بنجاح',
            'update-success' => 'تم التحديث بنجاح',
            'invalid'        => 'بيانات الاعتماد غير صالحة',
            'invalidurl'     => 'رابط غير صالح',
            'already_taken'  => 'تم استخدام عنوان URL للمتجر بالفعل.',
            'index'          => [
                'title'                 => 'بيانات اعتماد شوبفاي',
                'create'                => 'إنشاء بيانات اعتماد',
                'url'                   => 'رابط شوبفاي',
                'shopifyurlplaceholder' => 'رابط شوبفاي (مثل http://demo.myshopify.com)',
                'accesstoken'           => 'رمز وصول API الإداري',
                'apiVersion'            => 'إصدار API',
                'save'                  => 'حفظ',
                'back-btn'              => 'عودة',
                'channel'               => 'قناة البيع',
                'locations'             => 'قائمة المواقع',
            ],
            'edit' => [
                'title'    => 'تحرير بيانات الاعتماد',
                'delete'   => 'حذف بيانات الاعتماد',
                'back-btn' => 'عودة',
                'update'   => 'تحديث',
                'save'     => 'حفظ',
            ],
            'datagrid' => [
                'shopUrl'    => 'رابط شوبفاي',
                'apiVersion' => 'إصدار API',
                'enabled'    => 'مفعل',
            ],
        ],
        'export' => [
            'mapping' => [
                'title'         => 'تعيينات التصدير',
                'back-btn'      => 'عودة',
                'save'          => 'حفظ',
                'created'       => 'تم إنشاء تعيين التصدير',
                'image'         => 'خاصية تستخدم كصورة',
                'metafields'    => 'خصائص تستخدم كحقول ميتا',
                'filed-shopify' => 'حقل في شوبفاي',
                'attribute'     => 'خاصية',
                'fixed-value'   => 'قيمة ثابتة',
            ],
            'setting' => [
                'title'                        => 'الإعدادات',
                'tags'                         => 'إعدادات تصدير العلامات',
                'enable_metric_tags_attribute' => 'هل تريد تضمين أسماء الوحدات المترية في العلامات أيضًا؟',
                'enable_named_tags_attribute'  => 'هل تريد تضمين العلامات كعلامات مسماة؟',
                'tagSeprator'                  => 'استخدام فاصل أسماء الخصائص في العلامات',
                'enable_tags_attribute'        => 'هل تريد تضمين اسم الخاصية في العلامات أيضًا؟',
                'metafields'                   => 'إعدادات تصدير الحقول الميتا',
                'metaFieldsKey'                => 'استخدام المفتاح للحقول الميتا كرمز الخاصية / التسمية',
                'metaFieldsNameSpace'          => 'استخدام النطاق للحقول الميتا كرمز مجموعة الخصائص / عام',
                'other-settings'               => 'إعدادات أخرى',
                'roundof-attribute-value'      => 'إزالة الكسور الإضافية من القيم المترية (مثل 201,2000 كـ 201.2)',
                'option_name_label'            => 'قيمة اسم الخيارات كاسم خاصية (افتراضيًا كود الخاصية)',
            ],

            'errors' => [
                'invalid-credential' => 'بيانات الاعتماد غير صالحة. بيانات الاعتماد معطلة أو غير صحيحة',
            ],
        ],
        'import' => [
            'mapping' => [
                'title'                => 'خرائط الاستيراد',
                'back-btn'             => 'رجوع',
                'save'                 => 'حفظ',
                'created'              => 'تم حفظ خريطة الاستيراد بنجاح',
                'image'                => 'السمة المستخدمة كصورة',
                'filed-shopify'        => 'الحقل في Shopify',
                'attribute'            => 'سمة UnoPim',
                'variantimage'         => 'السمة المستخدمة كصورة للمتغير',
                'other'                => 'خرائط أخرى في Shopify',
                'family'               => 'تعيين العائلة (للمنتجات)',
                'metafieldDefinitions' => 'تعيين تعريف الحقول الوصفية في Shopify',
            ],
            'setting' => [
                'credentialmapping' => 'تعيين بيانات الاعتماد',
            ],
            'job' => [
                'product' => [
                    'family-not-exist'      => 'العائلة غير موجودة للعنوان: - :title أولاً تحتاج إلى استيراد العائلة',
                    'variant-sku-not-exist' => 'لم يتم العثور على SKU للمتغير في المنتج: - :id',
                    'duplicate-sku'         => ':sku : - تم العثور على SKU مكرر في المنتج',
                    'required-field'        => ':attribute : - الحقل مطلوب للـ SKU: - :sku',
                    'family-not-mapping'    => 'العائلة غير مخصصة للعنوان: - :title',
                    'attribute-not-exist'   => ':attributes السمة غير موجودة للمنتج',
                    'not-found-sku'         => 'SKU غير موجود في المنتج: - :id',
                    'option-not-found'      => ':attribute - :option الخيار غير موجود في SKU في UnoPim: - :sku',
                ],
            ],
        ],

        'fields' => [
            'name'                        => 'اسم',
            'description'                 => 'وصف',
            'price'                       => 'سعر',
            'weight'                      => 'وزن',
            'quantity'                    => 'كمية',
            'inventory_tracked'           => 'تتبع المخزون',
            'allow_purchase_out_of_stock' => 'السماح بالشراء عند نفاد المخزون',
            'vendor'                      => 'بائع',
            'product_type'                => 'نوع المنتج',
            'tags'                        => 'علامات',
            'barcode'                     => 'رمز شريطي',
            'compare_at_price'            => 'سعر المقارنة',
            'seo_title'                   => 'عنوان SEO',
            'seo_description'             => 'وصف SEO',
            'handle'                      => 'معالجة',
            'taxable'                     => 'قابل للضريبة',
            'inventory_cost'              => 'تكلفة المخزون',
        ],
        'exportmapping' => 'تعيينات الخصائص',
        'job'           => [
            'credentials'      => 'بيانات اعتماد Shopify',
            'channel'          => 'القناة',
            'currency'         => 'العملة',
            'productfilter'    => 'مرشح المنتج (SKU)',
            'locale'           => 'اللغة',
            'attribute-groups' => 'مجموعات السمات',
        ],
        'metafield'     => [
            'datagrid' => [
                'definitiontype'  => 'تستخدم لـ',
                'attribute-label'  => 'سمة Unopim',
                'definitionName'  => 'اسم التعريف',
                'contentTypeName' => 'نوع',
                'pin'             => 'دبوس',
            ],
            'index'    => [
                'title'                     => 'تعريفات الحقول الوصفية',
                'create'                    => 'إضافة تعريف',
                'definitiontype'            => 'تستخدم لـ',
                'attribute'                 => 'سمة UnoPim',
                'ContentTypeName'           => 'نوع',
                'attributes'                => 'اسم التعريف',
                'urlvalidation'             => 'التحقق من صحة URL',
                'urlvalidationdata'         => 'يجب أن تكون القيم مسبوقة بـ: "HTTPS"، "HTTP"، "mailto:"، "sms:"، أو "tel:"',
                'name_space_key'            => 'المجال والمفتاح',
                'description'               => 'وصف',
                'onevalue'                  => 'قيمة واحدة',
                'listvalue'                 => 'قائمة القيم',
                'validation'                => 'التحقق',
                'maxvalue'                  => 'القيمة القصوى',
                'adminFilterable'           => 'التصفية للمنتجات',
                'smartCollectionCondition'  => 'مجموعات ذكية',
                'storefronts'               => 'الوصول إلى الواجهات الأمامية',
            ],

            'type' => [
                'single_line_text_field' => 'سطر نصي واحد',
                'color'                  => 'لون',
                'rating'                 => 'تقييم',
                'url'                    => 'رابط',
                'multi_line_text_field'  => 'نص متعدد الأسطر',
                'json'                   => 'JSON',
                'boolean'                => 'صح أو خطأ',
                'date'                   => 'تاريخ',
                'number_decimal'         => 'عدد عشري',
                'number_integer'         => 'عدد صحيح',
                'dimension'              => 'أبعاد',
                'weight'                 => 'وزن',
                'volume'                 => 'حجم',
            ],

            'edit'     => [
                'title'           => 'تحرير تعريف الحقل الوصفي',
                'back-btn'        => 'رجوع',
                'update'          => 'تحديث',
                'save'            => 'حفظ',
            ],
            'delete-success'      => 'تم حذف تعريف الحقل الوصفي بنجاح',
            'update-success'      => 'تم تحديث تعريف الحقل الوصفي بنجاح',
            'created'             => 'تم إنشاء تعريف الحقل الوصفي بنجاح',
            'mass-delete-success' => 'تم حذف تعريفات الحقول الوصفية بنجاح',
        ],

    ],
];
