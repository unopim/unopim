<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Package Language Lines - Arabic
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the Order package (Arabic).
    |
    */

    // ACL Permission Labels
    'acl' => [
        'order' => 'الطلبات',
        'orders' => 'إدارة الطلبات',
        'orders.view' => 'عرض الطلبات',
        'orders.create' => 'إنشاء الطلبات',
        'orders.edit' => 'تعديل الطلبات',
        'orders.delete' => 'حذف الطلبات',
        'sync' => 'مزامنة الطلبات',
        'sync.view' => 'عرض سجلات المزامنة',
        'sync.trigger' => 'تفعيل المزامنة',
        'sync.retry' => 'إعادة محاولة المزامنة الفاشلة',
        'sync.configure' => 'تكوين إعدادات المزامنة',
        'profitability' => 'تحليل الربحية',
        'profitability.view' => 'عرض الربحية',
        'profitability.calculate' => 'حساب الربحية',
        'profitability.export' => 'تصدير التقارير',
        'webhooks' => 'خطافات الطلبات',
        'webhooks.view' => 'عرض الخطافات',
        'webhooks.create' => 'إنشاء الخطافات',
        'webhooks.edit' => 'تعديل الخطافات',
        'webhooks.delete' => 'حذف الخطافات',
        'webhooks.test' => 'اختبار الخطافات',
        'settings' => 'إعدادات الطلبات',
        'settings.view' => 'عرض الإعدادات',
        'settings.edit' => 'تعديل الإعدادات',
        'items' => 'عناصر الطلب',
        'items.view' => 'عرض عناصر الطلب',
        'items.edit' => 'تعديل عناصر الطلب',
        'history' => 'سجل الطلبات',
        'history.view' => 'عرض سجل الطلبات',
    ],

    // Menu Labels
    'menu' => [
        'orders' => 'الطلبات',
        'order-management' => 'إدارة الطلبات',
        'order-sync' => 'مزامنة الطلبات',
        'profitability-analysis' => 'تحليل الربحية',
        'order-webhooks' => 'خطافات الطلبات',
        'order-settings' => 'إعدادات الطلبات',
    ],

    // Orders Index Page
    'orders' => [
        'index' => [
            'title' => 'الطلبات',
            'create-btn' => 'إنشاء طلب',
            'export-btn' => 'تصدير الطلبات',
            'sync-btn' => 'مزامنة الطلبات',
            'filter-btn' => 'تصفية',
            'reset-btn' => 'إعادة تعيين',
            'search-placeholder' => 'البحث عن الطلبات...',
            'no-orders' => 'لا توجد طلبات',
            'total-orders' => 'إجمالي الطلبات',
            'today-orders' => 'طلبات اليوم',
            'pending-orders' => 'الطلبات المعلقة',
            'revenue-today' => 'إيرادات اليوم',
        ],

        // Create/Edit Order
        'create' => [
            'title' => 'إنشاء طلب',
            'success' => 'تم إنشاء الطلب بنجاح',
            'error' => 'فشل إنشاء الطلب',
        ],

        'edit' => [
            'title' => 'تعديل الطلب',
            'success' => 'تم تحديث الطلب بنجاح',
            'error' => 'فشل تحديث الطلب',
            'back-btn' => 'العودة إلى الطلبات',
            'save-btn' => 'حفظ الطلب',
        ],

        'delete' => [
            'title' => 'حذف الطلب',
            'confirm' => 'هل أنت متأكد من حذف هذا الطلب؟',
            'success' => 'تم حذف الطلب بنجاح',
            'error' => 'فشل حذف الطلب',
        ],

        // View Order Details
        'view' => [
            'title' => 'تفاصيل الطلب',
            'order-info' => 'معلومات الطلب',
            'customer-info' => 'معلومات العميل',
            'payment-info' => 'معلومات الدفع',
            'shipping-info' => 'معلومات الشحن',
            'items-info' => 'عناصر الطلب',
            'history-info' => 'سجل الطلب',
            'profitability-info' => 'تحليل الربحية',
            'print-btn' => 'طباعة الطلب',
            'invoice-btn' => 'إنشاء فاتورة',
        ],

        // Order Fields
        'fields' => [
            'order-number' => 'رقم الطلب',
            'channel' => 'القناة',
            'channel-order-id' => 'معرف طلب القناة',
            'customer-name' => 'اسم العميل',
            'customer-email' => 'البريد الإلكتروني للعميل',
            'customer-phone' => 'هاتف العميل',
            'status' => 'حالة الطلب',
            'payment-status' => 'حالة الدفع',
            'payment-method' => 'طريقة الدفع',
            'shipping-method' => 'طريقة الشحن',
            'total-amount' => 'المبلغ الإجمالي',
            'subtotal' => 'المجموع الفرعي',
            'tax-amount' => 'مبلغ الضريبة',
            'shipping-amount' => 'مبلغ الشحن',
            'discount-amount' => 'مبلغ الخصم',
            'order-date' => 'تاريخ الطلب',
            'created-at' => 'تاريخ الإنشاء',
            'updated-at' => 'تاريخ التحديث',
            'items-count' => 'عدد العناصر',
            'profit' => 'الربح',
            'margin-percentage' => 'نسبة الهامش %',
            'cost-price' => 'سعر التكلفة',
            'selling-price' => 'سعر البيع',
            'currency' => 'العملة',
            'notes' => 'ملاحظات',
            'internal-notes' => 'ملاحظات داخلية',
            'shipping-address' => 'عنوان الشحن',
            'billing-address' => 'عنوان الفواتير',
            'tracking-number' => 'رقم التتبع',
            'carrier' => 'شركة الشحن',
        ],

        // Order Items
        'items' => [
            'title' => 'عناصر الطلب',
            'sku' => 'رمز المنتج',
            'product-name' => 'اسم المنتج',
            'quantity' => 'الكمية',
            'unit-price' => 'سعر الوحدة',
            'total-price' => 'السعر الإجمالي',
            'discount' => 'الخصم',
            'tax' => 'الضريبة',
            'cost-price' => 'سعر التكلفة',
            'profit' => 'الربح',
            'no-items' => 'لا توجد عناصر في هذا الطلب',
        ],
    ],

    // Order Status
    'status' => [
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد المعالجة',
        'confirmed' => 'مؤكد',
        'shipped' => 'تم الشحن',
        'delivered' => 'تم التسليم',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
        'refunded' => 'تم الاسترداد',
        'on-hold' => 'معلق',
        'failed' => 'فشل',
    ],

    // Payment Status
    'payment-status' => [
        'unpaid' => 'غير مدفوع',
        'paid' => 'مدفوع',
        'partially-paid' => 'مدفوع جزئياً',
        'refunded' => 'تم الاسترداد',
        'pending' => 'قيد الانتظار',
        'failed' => 'فشل',
        'authorized' => 'مصرح به',
        'captured' => 'تم الالتقاط',
    ],

    // Order Synchronization
    'sync' => [
        'index' => [
            'title' => 'مزامنة الطلبات',
            'sync-now-btn' => 'مزامنة الآن',
            'sync-history' => 'سجل المزامنة',
            'last-sync' => 'آخر مزامنة',
            'next-sync' => 'المزامنة التالية',
            'auto-sync-enabled' => 'المزامنة التلقائية مفعلة',
            'manual-sync' => 'مزامنة يدوية',
        ],

        'create' => [
            'title' => 'تفعيل مزامنة الطلبات',
            'select-channel' => 'اختر القناة',
            'select-date-range' => 'اختر نطاق التاريخ',
            'sync-type' => 'نوع المزامنة',
            'full-sync' => 'مزامنة كاملة',
            'incremental-sync' => 'مزامنة تدريجية',
            'start-sync-btn' => 'بدء المزامنة',
        ],

        'status' => [
            'queued' => 'في قائمة الانتظار',
            'in-progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            'partial' => 'نجاح جزئي',
            'cancelled' => 'ملغى',
        ],

        'fields' => [
            'sync-id' => 'معرف المزامنة',
            'channel' => 'القناة',
            'sync-type' => 'نوع المزامنة',
            'status' => 'الحالة',
            'started-at' => 'بدأت في',
            'completed-at' => 'اكتملت في',
            'total-orders' => 'إجمالي الطلبات',
            'synced-orders' => 'الطلبات المتزامنة',
            'failed-orders' => 'الطلبات الفاشلة',
            'error-message' => 'رسالة الخطأ',
            'duration' => 'المدة',
        ],

        'actions' => [
            'view-details' => 'عرض التفاصيل',
            'retry' => 'إعادة المحاولة',
            'cancel' => 'إلغاء',
            'download-log' => 'تنزيل السجل',
        ],

        'messages' => [
            'sync-started' => 'تم بدء مزامنة الطلبات بنجاح لقناة :channel',
            'sync-completed' => 'تمت مزامنة الطلبات بنجاح. تم مزامنة :count طلب.',
            'sync-failed' => 'فشلت مزامنة الطلبات: :error',
            'sync-cancelled' => 'تم إلغاء مزامنة الطلبات',
            'retry-success' => 'تم بدء إعادة محاولة المزامنة بنجاح',
            'no-orders-to-sync' => 'لا توجد طلبات للمزامنة للمعايير المحددة',
        ],
    ],

    // Profitability Analysis
    'profitability' => [
        'index' => [
            'title' => 'تحليل الربحية',
            'calculate-btn' => 'حساب الربحية',
            'export-btn' => 'تصدير التقرير',
            'date-range' => 'نطاق التاريخ',
            'filter-by-channel' => 'تصفية حسب القناة',
            'filter-by-status' => 'تصفية حسب الحالة',
        ],

        'summary' => [
            'title' => 'ملخص الربحية',
            'total-revenue' => 'إجمالي الإيرادات',
            'total-cost' => 'إجمالي التكلفة',
            'total-profit' => 'إجمالي الربح',
            'average-margin' => 'متوسط الهامش',
            'profit-by-channel' => 'الربح حسب القناة',
            'profit-by-product' => 'الربح حسب المنتج',
            'profit-trend' => 'اتجاه الربح',
        ],

        'fields' => [
            'order-number' => 'رقم الطلب',
            'revenue' => 'الإيرادات',
            'cost' => 'التكلفة',
            'profit' => 'الربح',
            'margin' => 'الهامش %',
            'channel' => 'القناة',
            'product' => 'المنتج',
            'category' => 'الفئة',
            'calculated-at' => 'محسوب في',
        ],

        'messages' => [
            'calculation-started' => 'تم بدء حساب الربحية',
            'calculation-completed' => 'تم حساب الربحية بنجاح لـ :count طلب',
            'calculation-failed' => 'فشل حساب الربحية: :error',
            'export-success' => 'تم تصدير التقرير بنجاح',
            'no-data' => 'لا توجد بيانات ربحية متاحة للمعايير المحددة',
        ],
    ],

    // Order Webhooks
    'webhooks' => [
        'index' => [
            'title' => 'خطافات الطلبات',
            'create-btn' => 'إنشاء خطاف',
            'test-btn' => 'اختبار الخطاف',
            'active-webhooks' => 'الخطافات النشطة',
            'inactive-webhooks' => 'الخطافات غير النشطة',
        ],

        'create' => [
            'title' => 'إنشاء خطاف',
            'success' => 'تم إنشاء الخطاف بنجاح',
            'error' => 'فشل إنشاء الخطاف',
        ],

        'edit' => [
            'title' => 'تعديل الخطاف',
            'success' => 'تم تحديث الخطاف بنجاح',
            'error' => 'فشل تحديث الخطاف',
        ],

        'delete' => [
            'confirm' => 'هل أنت متأكد من حذف هذا الخطاف؟',
            'success' => 'تم حذف الخطاف بنجاح',
            'error' => 'فشل حذف الخطاف',
        ],

        'fields' => [
            'name' => 'اسم الخطاف',
            'url' => 'رابط الخطاف',
            'event' => 'الحدث',
            'channels' => 'القنوات',
            'active' => 'نشط',
            'secret' => 'المفتاح السري',
            'headers' => 'رؤوس مخصصة',
            'retry-count' => 'عدد المحاولات',
            'timeout' => 'المهلة (ثواني)',
            'last-triggered' => 'آخر تفعيل',
            'success-count' => 'عدد النجاحات',
            'failure-count' => 'عدد الإخفاقات',
        ],

        'events' => [
            'order-created' => 'تم إنشاء الطلب',
            'order-updated' => 'تم تحديث الطلب',
            'order-cancelled' => 'تم إلغاء الطلب',
            'order-completed' => 'تم إكمال الطلب',
            'order-refunded' => 'تم استرداد الطلب',
            'payment-received' => 'تم استلام الدفعة',
            'order-shipped' => 'تم شحن الطلب',
            'order-delivered' => 'تم تسليم الطلب',
        ],

        'messages' => [
            'test-success' => 'تم اختبار الخطاف بنجاح',
            'test-failed' => 'فشل اختبار الخطاف: :error',
            'triggered' => 'تم تفعيل الخطاف بنجاح',
            'trigger-failed' => 'فشل تفعيل الخطاف: :error',
        ],
    ],

    // Order Settings
    'settings' => [
        'index' => [
            'title' => 'إعدادات الطلبات',
            'general-settings' => 'الإعدادات العامة',
            'sync-settings' => 'إعدادات المزامنة',
            'notification-settings' => 'إعدادات الإشعارات',
            'save-btn' => 'حفظ الإعدادات',
        ],

        'general' => [
            'auto-approve-orders' => 'الموافقة التلقائية على الطلبات',
            'order-number-prefix' => 'بادئة رقم الطلب',
            'default-currency' => 'العملة الافتراضية',
            'allow-guest-orders' => 'السماح بطلبات الضيوف',
            'minimum-order-amount' => 'الحد الأدنى لمبلغ الطلب',
            'maximum-order-amount' => 'الحد الأقصى لمبلغ الطلب',
        ],

        'sync' => [
            'enable-auto-sync' => 'تفعيل المزامنة التلقائية',
            'sync-interval' => 'فاصل المزامنة (دقائق)',
            'sync-channels' => 'القنوات المراد مزامنتها',
            'sync-order-status' => 'مزامنة تحديثات حالة الطلب',
            'sync-inventory' => 'مزامنة تحديثات المخزون',
            'retry-failed-sync' => 'إعادة محاولة المزامنة الفاشلة تلقائياً',
            'max-retry-attempts' => 'الحد الأقصى لمحاولات الإعادة',
        ],

        'notifications' => [
            'notify-on-new-order' => 'الإشعار عند طلب جديد',
            'notify-on-status-change' => 'الإشعار عند تغيير الحالة',
            'notify-on-sync-failure' => 'الإشعار عند فشل المزامنة',
            'notification-email' => 'البريد الإلكتروني للإشعارات',
            'notification-channels' => 'قنوات الإشعارات',
        ],

        'messages' => [
            'save-success' => 'تم حفظ الإعدادات بنجاح',
            'save-error' => 'فشل حفظ الإعدادات',
            'reset-success' => 'تم إعادة تعيين الإعدادات إلى الافتراضية',
        ],
    ],

    // Validation Messages
    'validation' => [
        'order-number-required' => 'رقم الطلب مطلوب',
        'order-number-unique' => 'يجب أن يكون رقم الطلب فريداً',
        'channel-required' => 'القناة مطلوبة',
        'customer-name-required' => 'اسم العميل مطلوب',
        'customer-email-required' => 'البريد الإلكتروني للعميل مطلوب',
        'customer-email-valid' => 'يجب أن يكون البريد الإلكتروني للعميل صالحاً',
        'status-required' => 'حالة الطلب مطلوبة',
        'status-invalid' => 'حالة الطلب غير صالحة',
        'total-amount-required' => 'المبلغ الإجمالي مطلوب',
        'total-amount-positive' => 'يجب أن يكون المبلغ الإجمالي موجباً',
        'items-required' => 'يجب أن يحتوي الطلب على عنصر واحد على الأقل',
        'webhook-url-required' => 'رابط الخطاف مطلوب',
        'webhook-url-valid' => 'يجب أن يكون رابط الخطاف صالحاً',
        'webhook-event-required' => 'حدث الخطاف مطلوب',
    ],

    // General Messages
    'messages' => [
        'create-success' => 'تم إنشاء الطلب بنجاح',
        'update-success' => 'تم تحديث الطلب بنجاح',
        'delete-success' => 'تم حذف الطلب بنجاح',
        'status-updated' => 'تم تحديث حالة الطلب إلى :status',
        'payment-status-updated' => 'تم تحديث حالة الدفع إلى :status',
        'sync-success' => 'تمت مزامنة الطلبات بنجاح من :channel',
        'sync-failed' => 'فشلت مزامنة الطلبات: :error',
        'webhook-created' => 'تم إنشاء الخطاف بنجاح',
        'profitability-calculated' => 'تم حساب الربحية بنجاح',
        'export-success' => 'تم تصدير الطلبات بنجاح',
        'import-success' => 'تم استيراد :count طلب بنجاح',
        'no-permission' => 'ليس لديك صلاحية لتنفيذ هذا الإجراء',
        'order-not-found' => 'الطلب غير موجود',
        'invalid-status-transition' => 'لا يمكن الانتقال من :from إلى :to',
    ],

    // DataGrid Column Headers
    'datagrid' => [
        'id' => 'المعرف',
        'order-number' => 'رقم الطلب',
        'channel' => 'القناة',
        'customer' => 'العميل',
        'status' => 'الحالة',
        'payment-status' => 'حالة الدفع',
        'total' => 'الإجمالي',
        'items' => 'العناصر',
        'date' => 'التاريخ',
        'actions' => 'الإجراءات',
        'profit' => 'الربح',
        'margin' => 'الهامش',
    ],

    // Filter Labels
    'filters' => [
        'all-orders' => 'جميع الطلبات',
        'pending' => 'قيد الانتظار',
        'processing' => 'قيد المعالجة',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغى',
        'date-range' => 'نطاق التاريخ',
        'channel' => 'القناة',
        'status' => 'الحالة',
        'payment-status' => 'حالة الدفع',
        'customer' => 'العميل',
        'apply' => 'تطبيق التصفية',
        'clear' => 'مسح التصفية',
    ],

    // Action Labels
    'actions' => [
        'view' => 'عرض',
        'edit' => 'تعديل',
        'delete' => 'حذف',
        'sync' => 'مزامنة',
        'export' => 'تصدير',
        'print' => 'طباعة',
        'invoice' => 'فاتورة',
        'cancel' => 'إلغاء الطلب',
        'refund' => 'استرداد',
        'ship' => 'تعيين كمشحون',
        'deliver' => 'تعيين كمسلم',
        'complete' => 'تعيين كمكتمل',
    ],

    // Tooltips and Help Text
    'tooltips' => [
        'order-number' => 'معرف فريد لهذا الطلب',
        'channel-order-id' => 'معرف الطلب الأصلي من قناة المبيعات',
        'auto-sync' => 'مزامنة الطلبات تلقائياً على فترات محددة',
        'webhook-secret' => 'يستخدم للتحقق من صحة الخطاف',
        'retry-count' => 'عدد المرات لإعادة محاولة تسليم الخطاف الفاشل',
        'profit-margin' => 'يتم حسابه كـ (سعر البيع - سعر التكلفة) / سعر البيع × 100',
    ],
];
