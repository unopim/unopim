<?php

return [
    'acl' => [
        'pricing'         => 'التسعير',
        'costs'           => 'تكاليف المنتجات',
        'channel-costs'   => 'تكاليف القنوات',
        'margins'         => 'حماية الهوامش',
        'recommendations' => 'توصيات الأسعار',
        'strategies'      => 'استراتيجيات التسعير',
        'view'            => 'عرض',
        'create'          => 'إنشاء',
        'edit'            => 'تعديل',
        'delete'          => 'حذف',
        'approve'         => 'موافقة',
        'reject'          => 'رفض',
        'apply'           => 'تطبيق',
    ],

    'components' => [
        'layouts' => [
            'sidebar' => [
                'pricing'         => 'التسعير',
                'costs'           => 'تكاليف المنتجات',
                'channel-costs'   => 'تكاليف القنوات',
                'recommendations' => 'التوصيات',
                'margins'         => 'حماية الهوامش',
                'strategies'      => 'الاستراتيجيات',
            ],
        ],
    ],

    'costs' => [
        'index' => [
            'title'      => 'تكاليف المنتجات',
            'create-btn' => 'إضافة تكلفة',
        ],

        'create' => [
            'title' => 'إضافة تكلفة منتج',
        ],

        'edit' => [
            'title' => 'تعديل تكلفة المنتج',
        ],

        'datagrid' => [
            'id'             => 'المعرف',
            'product'        => 'المنتج',
            'cost-type'      => 'نوع التكلفة',
            'amount'         => 'المبلغ',
            'currency'       => 'العملة',
            'effective-from' => 'سارية من',
            'effective-to'   => 'سارية حتى',
            'created-by'     => 'أنشأها',
        ],

        'fields' => [
            'product'        => 'المنتج',
            'cost-type'      => 'نوع التكلفة',
            'amount'         => 'المبلغ',
            'currency-code'  => 'العملة',
            'effective-from' => 'سارية من',
            'effective-to'   => 'سارية حتى',
            'notes'          => 'ملاحظات',
        ],

        'cost-types' => [
            'cogs'        => 'تكلفة البضاعة المباعة',
            'operational' => 'تشغيلية',
            'marketing'   => 'تسويقية',
            'platform'    => 'منصة',
            'shipping'    => 'شحن',
            'overhead'    => 'نفقات عامة',
        ],

        'create-success' => 'تم إنشاء تكلفة المنتج بنجاح.',
        'update-success' => 'تم تحديث تكلفة المنتج بنجاح.',
        'delete-success' => 'تم حذف تكلفة المنتج بنجاح.',
        'delete-failed'  => 'لا يمكن حذف تكلفة المنتج.',
    ],

    'channel-costs' => [
        'index' => [
            'title'      => 'تكاليف القنوات',
            'create-btn' => 'إضافة تكلفة قناة',
        ],

        'datagrid' => [
            'id'                           => 'المعرف',
            'channel'                      => 'القناة',
            'commission-percentage'         => 'نسبة العمولة',
            'fixed-fee-per-order'          => 'رسوم ثابتة / طلب',
            'payment-processing-percentage' => 'نسبة معالجة الدفع',
            'payment-fixed-fee'            => 'رسوم الدفع الثابتة',
            'effective-from'               => 'سارية من',
            'effective-to'                 => 'سارية حتى',
        ],

        'fields' => [
            'channel'                       => 'القناة',
            'commission-percentage'         => 'نسبة العمولة',
            'fixed-fee-per-order'          => 'رسوم ثابتة لكل طلب',
            'payment-processing-percentage' => 'نسبة معالجة الدفع',
            'payment-fixed-fee'            => 'رسوم الدفع الثابتة',
            'shipping-cost-per-zone'       => 'تكلفة الشحن لكل منطقة',
            'currency-code'                => 'العملة',
            'effective-from'               => 'سارية من',
            'effective-to'                 => 'سارية حتى',
        ],

        'create-success' => 'تم إنشاء تكلفة القناة بنجاح.',
        'update-success' => 'تم تحديث تكلفة القناة بنجاح.',
    ],

    'break-even' => [
        'title'              => 'تحليل نقطة التعادل',
        'product'            => 'المنتج',
        'channel'            => 'القناة',
        'total-cost'         => 'التكلفة الإجمالية',
        'break-even-price'   => 'سعر التعادل',
        'current-price'      => 'السعر الحالي',
        'margin'             => 'الهامش',
        'margin-percentage'  => 'نسبة الهامش',
        'cost-breakdown'     => 'تفصيل التكاليف',
        'no-costs'           => 'لا توجد بيانات تكلفة لهذا المنتج.',
    ],

    'recommendations' => [
        'title'               => 'توصيات الأسعار',
        'product'             => 'المنتج',
        'channel'             => 'القناة',
        'current-price'       => 'السعر الحالي',
        'recommended-minimum' => 'الحد الأدنى الموصى به',
        'recommended-target'  => 'السعر المستهدف الموصى به',
        'recommended-premium' => 'السعر المميز الموصى به',
        'apply-btn'           => 'تطبيق السعر',
        'select-tier'         => 'اختر مستوى التسعير',

        'tiers' => [
            'minimum' => 'الحد الأدنى للهامش',
            'target'  => 'الهامش المستهدف',
            'premium' => 'الهامش المميز',
        ],

        'apply-success' => 'تم تطبيق السعر الموصى به بنجاح.',
        'apply-failed'  => 'فشل تطبيق السعر الموصى به.',
    ],

    'margins' => [
        'index' => [
            'title' => 'أحداث حماية الهوامش',
        ],

        'show' => [
            'title' => 'تفاصيل حدث الهامش',
        ],

        'datagrid' => [
            'id'                       => 'المعرف',
            'product'                  => 'المنتج',
            'channel'                  => 'القناة',
            'event-type'               => 'نوع الحدث',
            'proposed-price'           => 'السعر المقترح',
            'break-even-price'         => 'سعر التعادل',
            'margin-percentage'        => 'نسبة الهامش',
            'minimum-margin-percentage' => 'الحد الأدنى لنسبة الهامش',
            'approved-by'              => 'وافق عليه',
            'created-at'               => 'تاريخ الإنشاء',
        ],

        'fields' => [
            'product'                   => 'المنتج',
            'channel'                   => 'القناة',
            'event-type'                => 'نوع الحدث',
            'proposed-price'            => 'السعر المقترح',
            'break-even-price'          => 'سعر التعادل',
            'minimum-margin-price'      => 'سعر الحد الأدنى للهامش',
            'target-margin-price'       => 'سعر الهامش المستهدف',
            'margin-percentage'         => 'نسبة الهامش',
            'minimum-margin-percentage' => 'الحد الأدنى لنسبة الهامش',
            'reason'                    => 'السبب',
            'approved-by'               => 'وافق عليه',
            'approved-at'               => 'تاريخ الموافقة',
            'expires-at'                => 'تنتهي في',
        ],

        'event-types' => [
            'blocked'  => 'محظور',
            'warning'  => 'تحذير',
            'approved' => 'موافق عليه',
            'expired'  => 'منتهي الصلاحية',
        ],

        'approve-success'  => 'تمت الموافقة على حدث الهامش بنجاح.',
        'approve-failed'   => 'فشلت الموافقة على حدث الهامش.',
        'reject-success'   => 'تم رفض حدث الهامش بنجاح.',
        'reject-failed'    => 'فشل رفض حدث الهامش.',
        'already-resolved' => 'تم حل حدث الهامش هذا بالفعل.',
    ],

    'strategies' => [
        'index' => [
            'title'      => 'استراتيجيات التسعير',
            'create-btn' => 'إنشاء استراتيجية',
        ],

        'create' => [
            'title' => 'إنشاء استراتيجية تسعير',
        ],

        'edit' => [
            'title' => 'تعديل استراتيجية التسعير',
        ],

        'datagrid' => [
            'id'                        => 'المعرف',
            'scope-type'                => 'نوع النطاق',
            'scope-id'                  => 'معرف النطاق',
            'minimum-margin-percentage' => 'الحد الأدنى لنسبة الهامش',
            'target-margin-percentage'  => 'نسبة الهامش المستهدف',
            'premium-margin-percentage' => 'نسبة الهامش المميز',
            'psychological-pricing'     => 'التسعير النفسي',
            'round-to'                  => 'تقريب إلى',
            'is-active'                 => 'نشط',
            'priority'                  => 'الأولوية',
        ],

        'fields' => [
            'scope-type'                => 'نوع النطاق',
            'scope-id'                  => 'معرف النطاق',
            'minimum-margin-percentage' => 'الحد الأدنى لنسبة الهامش',
            'target-margin-percentage'  => 'نسبة الهامش المستهدف',
            'premium-margin-percentage' => 'نسبة الهامش المميز',
            'psychological-pricing'     => 'التسعير النفسي',
            'round-to'                  => 'تقريب إلى',
            'is-active'                 => 'نشط',
            'priority'                  => 'الأولوية',
        ],

        'scope-types' => [
            'global'   => 'عام',
            'category' => 'تصنيف',
            'channel'  => 'قناة',
            'product'  => 'منتج',
        ],

        'round-to-options' => [
            '0.99' => 'x.99',
            '0.95' => 'x.95',
            '0.00' => 'x.00',
            'none' => 'بدون تقريب',
        ],

        'create-success' => 'تم إنشاء استراتيجية التسعير بنجاح.',
        'update-success' => 'تم تحديث استراتيجية التسعير بنجاح.',
        'delete-success' => 'تم حذف استراتيجية التسعير بنجاح.',
        'delete-failed'  => 'لا يمكن حذف استراتيجية التسعير.',
    ],

    'validation' => [
        'amount-required'          => 'حقل المبلغ مطلوب.',
        'amount-numeric'           => 'يجب أن يكون المبلغ رقمًا.',
        'amount-min'               => 'يجب أن يكون المبلغ على الأقل :min.',
        'cost-type-required'       => 'حقل نوع التكلفة مطلوب.',
        'cost-type-invalid'        => 'نوع التكلفة المحدد غير صالح.',
        'product-required'         => 'حقل المنتج مطلوب.',
        'channel-required'         => 'حقل القناة مطلوب.',
        'effective-from-required'  => 'حقل تاريخ السريان مطلوب.',
        'effective-from-date'      => 'يجب أن يكون تاريخ السريان تاريخًا صالحًا.',
        'effective-to-after'       => 'يجب أن يكون تاريخ الانتهاء بعد تاريخ السريان.',
        'scope-type-required'      => 'حقل نوع النطاق مطلوب.',
        'scope-type-invalid'       => 'نوع النطاق المحدد غير صالح.',
        'margin-min'               => 'يجب أن تكون نسبة الهامش على الأقل :min.',
        'margin-max'               => 'يجب ألا تتجاوز نسبة الهامش :max.',
        'tier-required'            => 'يجب اختيار مستوى تسعير.',
        'tier-invalid'             => 'مستوى التسعير المحدد غير صالح.',
    ],

    'errors' => [
        'PRC-001' => 'المنتج غير موجود.',
        'PRC-002' => 'القناة غير موجودة.',
        'PRC-003' => 'لا تتوفر بيانات تكلفة لهذا المنتج.',
        'PRC-004' => 'لم يتم العثور على استراتيجية تسعير نشطة.',
        'PRC-005' => 'انتهاك الهامش: السعر المقترح أقل من نقطة التعادل.',
        'PRC-006' => 'إدخال تكلفة مكرر لنفس المنتج والنوع وتاريخ السريان.',
        'PRC-007' => 'لا يمكن حذف تكلفة مع وجود أحداث حماية هوامش نشطة.',
        'PRC-008' => 'تعارض نطاق الاستراتيجية: توجد استراتيجية بالفعل لهذا النطاق.',
        'PRC-009' => 'رمز عملة غير صالح.',
        'PRC-010' => 'تم حل حدث الهامش بالفعل.',
    ],

    'general' => [
        'save'    => 'حفظ',
        'cancel'  => 'إلغاء',
        'confirm' => 'هل أنت متأكد؟',
        'yes'     => 'نعم',
        'no'      => 'لا',
        'back'    => 'رجوع',
        'actions' => 'إجراءات',
        'approve' => 'موافقة',
        'reject'  => 'رفض',
        'apply'   => 'تطبيق',
    ],
];
