<?php

return [
    'acl' => [
        'channel-connectors' => 'موصلات القنوات',
        'connectors'         => 'الموصلات',
        'mappings'           => 'ربط الحقول',
        'sync'               => 'مهام المزامنة',
        'conflicts'          => 'تعارضات المزامنة',
        'webhooks'           => 'ويب هوك',
        'view'               => 'عرض',
        'create'             => 'إنشاء',
        'edit'               => 'تعديل',
        'delete'             => 'حذف',
        'manage'             => 'إدارة',
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'channel-connectors' => 'موصلات القنوات',
                'connectors'         => 'الموصلات',
                'sync-monitor'       => 'مراقب المزامنة',
                'conflicts'          => 'التعارضات',
            ],
        ],
    ],

    'connectors' => [
        'index' => [
            'title'      => 'موصلات القنوات',
            'create-btn' => 'إنشاء موصل',
        ],

        'create' => [
            'title' => 'إنشاء موصل قناة',
        ],

        'edit' => [
            'title' => 'تعديل موصل القناة',
        ],

        'datagrid' => [
            'code'           => 'الرمز',
            'name'           => 'الاسم',
            'channel-type'   => 'نوع القناة',
            'status'         => 'الحالة',
            'last-synced-at' => 'آخر مزامنة',
        ],

        'create-success'    => 'تم إنشاء الموصل بنجاح.',
        'update-success'    => 'تم تحديث الموصل بنجاح.',
        'delete-success'    => 'تم حذف الموصل بنجاح.',
        'delete-failed'     => 'لا يمكن حذف الموصل.',
        'test-success'      => 'تم التحقق من الاتصال بنجاح.',
        'test-failed'       => 'فشل اختبار الاتصال: :reason',
        'duplicate-running' => 'مهمة مزامنة قيد التشغيل بالفعل لهذا الموصل.',

        'status' => [
            'connected'    => 'متصل',
            'disconnected' => 'غير متصل',
            'error'        => 'خطأ',
        ],

        'channel-types' => [
            'shopify'      => 'شوبيفاي',
            'salla'        => 'سلة',
            'easy_orders'  => 'إيزي أوردرز',
        ],

        'fields' => [
            'code'              => 'الرمز',
            'name'              => 'الاسم',
            'channel-type'      => 'نوع القناة',
            'credentials'       => 'بيانات الاعتماد',
            'shop-url'          => 'رابط المتجر',
            'access-token'      => 'رمز الوصول',
            'api-key'           => 'مفتاح API',
            'status'            => 'الحالة',
            'conflict-strategy' => 'استراتيجية التعارض الافتراضية',
            'inbound-strategy'  => 'استراتيجية ويب هوك الواردة',
            'access-token-help' => 'اتركه فارغاً للاحتفاظ ببيانات الاعتماد الحالية.',
        ],

        'conflict-strategies' => [
            'always_ask'          => 'السؤال دائماً',
            'pim_always_wins'     => 'PIM يفوز دائماً',
            'channel_always_wins' => 'القناة تفوز دائماً',
        ],

        'conflict-strategy-help' => 'يحدد كيفية التعامل مع التعارضات عندما يتم تعديل المنتج نفسه في كل من PIM والقناة منذ آخر مزامنة.',

        'inbound-strategies' => [
            'auto_update'      => 'تحديث PIM تلقائياً',
            'flag_for_review'  => 'وضع علامة للمراجعة',
            'ignore'           => 'تجاهل',
        ],
    ],

    'mappings' => [
        'index' => [
            'title' => 'ربط الحقول',
        ],

        'save-success'     => 'تم حفظ الربط بنجاح.',
        'save-failed'      => 'فشل في حفظ الربط.',
        'translatable'     => 'قابل للترجمة',
        'auto-suggest'     => 'اقتراح تلقائي',
        'preview'          => 'معاينة',

        'direction' => [
            'export' => 'تصدير',
            'import' => 'استيراد',
            'both'   => 'كلاهما',
        ],

        'fields' => [
            'unopim-attribute' => 'خاصية UnoPim',
            'channel-field'    => 'حقل القناة',
            'direction'        => 'الاتجاه',
            'transformation'   => 'التحويل',
            'locale-mapping'   => 'ربط اللغة',
        ],

        'actions' => [
            'add'               => 'إضافة ربط',
            'apply-suggestions' => 'تطبيق الاقتراحات',
        ],

        'locale-mapping' => [
            'title'          => 'ربط اللغة',
            'unopim-locale'  => 'لغة UnoPim',
            'channel-locale' => 'لغة القناة',
            'unmapped'       => 'غير مربوط (سيتم تخطيه)',
            'rtl-warning'    => 'قد يتم تعديل محتوى RTL لهذه القناة.',
        ],
    ],

    'sync' => [
        'index' => [
            'title' => 'مهام المزامنة',
            'empty' => 'لا توجد مهام مزامنة بعد.',
        ],

        'show' => [
            'title'            => 'تفاصيل مهمة المزامنة',
            'percent-complete' => 'مكتمل',
        ],

        'trigger-success' => 'تم وضع مهمة المزامنة في قائمة الانتظار بنجاح.',
        'retry-success'   => 'تم وضع مهمة إعادة المحاولة في قائمة الانتظار بنجاح.',
        'trigger-failed'  => 'فشل في بدء مهمة المزامنة.',

        'status' => [
            'pending'   => 'قيد الانتظار',
            'running'   => 'قيد التشغيل',
            'completed' => 'مكتملة',
            'failed'    => 'فاشلة',
            'retrying'  => 'إعادة المحاولة',
        ],

        'types' => [
            'full'        => 'مزامنة كاملة',
            'incremental' => 'مزامنة تزايدية',
            'single'      => 'منتج واحد',
        ],

        'fields' => [
            'connector'        => 'الموصل',
            'sync-type'        => 'نوع المزامنة',
            'status'           => 'الحالة',
            'total-products'   => 'إجمالي المنتجات',
            'synced-products'  => 'تمت مزامنتها',
            'failed-products'  => 'فاشلة',
            'started-at'       => 'بدأت في',
            'completed-at'     => 'اكتملت في',
            'duration'         => 'المدة',
            'progress'         => 'التقدم',
        ],

        'actions' => [
            'trigger-sync'    => 'بدء المزامنة',
            'trigger-full'    => 'مزامنة كاملة',
            'trigger-incr'    => 'مزامنة تزايدية',
            'retry-failed'    => 'إعادة المحاولة',
            'confirm-full'    => 'المزامنة الكاملة ستعالج جميع المنتجات. قد يستغرق ذلك بعض الوقت. هل تريد المتابعة؟',
        ],

        'errors' => [
            'title'      => 'تفاصيل الخطأ',
            'product'    => 'المنتج',
            'error-code' => 'رمز الخطأ',
            'message'    => 'الرسالة',
        ],
    ],

    'conflicts' => [
        'index' => [
            'title' => 'تعارضات المزامنة',
        ],

        'show' => [
            'title'            => 'تفاصيل التعارض',
            'field-comparison' => 'مقارنة الحقول',
            'common'           => 'مشترك',
            'field'            => 'الحقل',
            'winner'           => 'الفائز',
            'pim'              => 'PIM',
            'channel'          => 'القناة',
            'locale'           => 'اللغة',
        ],

        'resolve-success'    => 'تم حل التعارض بنجاح.',
        'resolve-failed'     => 'فشل في حل التعارض.',
        'already-resolved'   => 'تم حل هذا التعارض بالفعل (:status).',
        'resolution-details' => 'تفاصيل الحل',

        'resolution' => [
            'pending'      => 'قيد الانتظار',
            'unresolved'   => 'غير محلول',
            'pim_wins'     => 'PIM يفوز',
            'channel_wins' => 'القناة تفوز',
            'merged'       => 'دمج يدوي',
            'dismissed'    => 'تم التجاهل',
        ],

        'conflict-types' => [
            'both_modified'      => 'تم التعديل في كليهما',
            'field_mismatch'     => 'عدم تطابق الحقول',
            'deleted_in_pim'     => 'محذوف في PIM',
            'deleted_in_channel' => 'محذوف في القناة',
            'new_in_channel'     => 'جديد في القناة',
        ],

        'fields' => [
            'product'             => 'المنتج',
            'connector'           => 'الموصل',
            'conflict-type'       => 'نوع التعارض',
            'resolution-status'   => 'حالة الحل',
            'pim-value'           => 'قيمة PIM',
            'channel-value'       => 'قيمة القناة',
            'pim-modified-at'     => 'تاريخ تعديل PIM',
            'channel-modified-at' => 'تاريخ تعديل القناة',
            'resolved-by'         => 'تم الحل بواسطة',
            'resolved-at'         => 'تاريخ الحل',
        ],

        'actions' => [
            'resolve'          => 'حل',
            'pim-wins-all'     => 'PIM يفوز (جميع الحقول)',
            'channel-wins-all' => 'القناة تفوز (جميع الحقول)',
            'dismiss'          => 'تجاهل',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title' => 'ويب هوك',
        ],

        'fields' => [
            'webhook-url'      => 'رابط ويب هوك',
            'events'           => 'الأحداث',
            'status'           => 'الحالة',
            'last-received'    => 'آخر استلام',
            'inbound-strategy' => 'الاستراتيجية الواردة',
        ],

        'events' => [
            'product-created' => 'تم إنشاء المنتج',
            'product-updated' => 'تم تحديث المنتج',
            'product-deleted' => 'تم حذف المنتج',
        ],

        'copy-url'              => 'نسخ الرابط',
        'url-copied'            => 'تم نسخ رابط ويب هوك إلى الحافظة.',
        'register-success'      => 'تم تسجيل ويب هوك بنجاح.',
        'unregister-success'    => 'تم إلغاء تسجيل ويب هوك بنجاح.',
        'manage-success'        => 'تم حفظ إعدادات ويب هوك بنجاح.',
        'no-token'              => 'سيتم إنشاء رمز ويب هوك عند حفظ الإعدادات.',
        'save-settings'         => 'حفظ إعدادات ويب هوك',
        'event-subscriptions'   => 'اشتراكات الأحداث',
        'webhook-url-info'      => 'استخدم هذا الرابط لتلقي إشعارات ويب هوك من قناتك.',
        'inbound-strategy-info' => 'يتحكم في كيفية معالجة بيانات ويب هوك الواردة.',
    ],

    'errors' => [
        'CHN-001' => 'نوع قناة غير صالح.',
        'CHN-002' => 'تنسيق بيانات الاعتماد غير صالح.',
        'CHN-003' => 'فشل اختبار الاتصال.',
        'CHN-004' => 'فشل مصادقة OAuth2.',
        'CHN-005' => 'فشل تجديد رمز OAuth2.',
        'CHN-010' => 'حقل القناة المطلوب مفقود: :field',
        'CHN-011' => 'عدم تطابق نوع الحقل لـ :field.',
        'CHN-012' => 'اللغة غير مدعومة من القناة: :locale',
        'CHN-013' => 'العملة غير مدعومة من القناة: :currency',
        'CHN-020' => 'تم تجاوز حد معدل طلبات API للقناة.',
        'CHN-021' => 'API القناة غير متاح مؤقتاً.',
        'CHN-022' => 'API القناة أرجع خطأ: :message',
        'CHN-023' => 'انتهت مهلة API القناة.',
        'CHN-030' => 'المنتج غير موجود في القناة.',
        'CHN-031' => 'فشل إنشاء المنتج في القناة.',
        'CHN-032' => 'فشل تحديث المنتج في القناة.',
        'CHN-033' => 'فشل حذف المنتج في القناة.',
        'CHN-040' => 'تم اكتشاف تعارض في المزامنة.',
        'CHN-041' => 'فشل حل التعارض.',
        'CHN-050' => 'توقيع ويب هوك غير صالح.',
        'CHN-051' => 'خطأ في تحليل حمولة ويب هوك.',
        'CHN-052' => 'نوع حدث ويب هوك غير مدعوم.',
        'CHN-060' => 'فشل التحقق من ربط الحقول.',
        'CHN-061' => 'ربط حقل معطل (تم حذف الخاصية).',
        'CHN-070' => 'خطأ في حساب الضريبة.',
        'CHN-071' => 'خطأ في تتبع العمولة.',
        'CHN-080' => 'انتهاك عزل المستأجر.',
        'CHN-090' => 'مهمة مزامنة قيد التشغيل بالفعل لهذا الموصل.',
        'CHN-091' => 'مصدر مهمة إعادة المحاولة غير موجود.',
    ],

    'dashboard' => [
        'title'              => 'مراقب المزامنة',
        'back-to-connectors' => 'العودة إلى الموصلات',
        'retry-only-failed'  => 'يمكن إعادة محاولة المهام الفاشلة فقط.',

        'datagrid' => [
            'id' => 'المعرف',
        ],

        'show' => [
            'title'         => 'تفاصيل مهمة المزامنة',
            'retry-history' => 'سجل إعادة المحاولة',
        ],

        'progress' => [
            'products-processed' => 'منتجات تمت معالجتها',
            'synced'             => 'تمت المزامنة',
            'failed'             => 'فشلت',
            'eta'                => 'الوقت المتوقع',
            'polling'            => 'مباشر',
        ],
    ],

    'general' => [
        'save'            => 'حفظ',
        'cancel'          => 'إلغاء',
        'confirm'         => 'هل أنت متأكد؟',
        'yes'             => 'نعم',
        'no'              => 'لا',
        'back'            => 'رجوع',
        'actions'         => 'إجراءات',
        'test-connection' => 'اختبار الاتصال',
        'view'            => 'عرض',
        'store'           => 'المتجر',
        'products'        => 'المنتجات',
    ],
];
