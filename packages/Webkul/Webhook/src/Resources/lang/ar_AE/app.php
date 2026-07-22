<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'الويب هوك',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'يرجى تفعيل Webhook من الإعدادات',
        'success'       => 'تم إرسال بيانات المنتج إلى Webhook بنجاح',
    ],
    'acl' => [
        'webhook' => [
            'index'  => 'ويب هوك',
            'create' => 'إنشاء',
            'edit'   => 'تعديل',
            'delete' => 'حذف',
        ],
        'settings' => [
            'index'  => 'الإعدادات',
            'update' => 'تحديث الإعدادات',
        ],
        'logs' => [
            'index'       => 'السجلات',
            'view'        => 'عرض',
            'delete'      => 'حذف',
            'mass-delete' => 'حذف جماعي',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'تم إنشاء المنتج',
            'updated' => 'تم تحديث المنتج',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'الويب هوك',
            'create-btn'   => 'إنشاء ويب هوك',
            'logs-btn'     => 'السجلات',
            'back-btn'     => 'العودة إلى الويب هوك',
            'default-name' => 'افتراضي',
            'datagrid'     => [
                'id'         => 'المعرف',
                'name'       => 'الاسم',
                'url'        => 'الرابط',
                'events'     => 'الأحداث',
                'status'     => 'الحالة',
                'active'     => 'نشط',
                'inactive'   => 'غير نشط',
                'created_at' => 'تاريخ الإنشاء',
                'edit'       => 'تعديل',
                'delete'     => 'حذف',
            ],
        ],
        'create' => [
            'title'    => 'إنشاء ويب هوك',
            'cancel'   => 'إلغاء',
            'save-btn' => 'حفظ',
        ],
        'edit' => [
            'title'    => 'تعديل ويب هوك',
            'cancel'   => 'إلغاء',
            'save-btn' => 'حفظ',
        ],
        'form' => [
            'general'       => 'عام',
            'name'          => 'الاسم',
            'url'           => 'الرابط',
            'events'        => 'الأحداث',
            'select-events' => 'اختر الأحداث',
            'secret'        => 'سر التوقيع',
            'secret-set'    => 'تم تعيين سر بالفعل',
            'secret-hint'   => 'يُستخدم لتوقيع كل حمولة بتوقيع HMAC SHA-256. اتركه فارغاً للاحتفاظ بالسر الحالي.',
            'settings'      => 'الإعدادات',
            'active'        => 'نشط',
            'test'          => 'اختبار الاتصال',
            'test-hint'     => 'إرسال طلب اختبار إلى الرابط أعلاه.',
            'test-btn'      => 'إرسال اختبار',
            'test-no-url'   => 'يرجى إدخال رابط أولاً.',
            'test-failed'   => 'فشل طلب الاختبار.',
            'headers'       => 'رؤوس مخصصة',
            'add-header'    => 'إضافة رأس',
            'no-headers'    => 'لم تتم إضافة رؤوس مخصصة.',
            'header-key'    => 'الرأس',
            'header-value'  => 'القيمة',
        ],
        'create-success' => 'تم إنشاء الويب هوك بنجاح',
        'update-success' => 'تم تحديث الويب هوك بنجاح',
        'delete-success' => 'تم حذف الويب هوك بنجاح',
        'delete-failed'  => 'فشل حذف الويب هوك',
        'validation'     => [
            'unsafe-url' => 'يشير الرابط إلى عنوان خاص أو محلي أو داخلي وغير مسموح به.',
            'scheme'     => 'يجب أن يبدأ الرابط بـ http:// أو https://.',
        ],
        'test' => [
            'payload-message'   => 'طلب اختبار ويب هوك Unopim',
            'connection-failed' => 'تعذّر الوصول إلى الرابط. يرجى التحقق من الرابط.',
            'unreachable'       => 'الرابط غير قابل للوصول (HTTP :code).',
            'reachable'         => 'الرابط قابل للوصول.',
        ],
        'prune' => [
            'disabled' => 'الاحتفاظ بسجلات الويب هوك معطّل؛ لم يتم حذف أي شيء.',
            'done'     => 'تم حذف :count سجل(سجلات) ويب هوك أقدم من :days يوم(أيام).',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'الإعدادات',
                    'save'    => 'حفظ',
                    'general' => 'عام',
                    'active'  => [
                        'label' => 'Webhook نشط',
                    ],
                    'webhook_url' => [
                        'label'             => 'رابط Webhook',
                        'required'          => 'يجب إدخال رابط Webhook عندما يكون Webhook مفعّلاً.',
                        'scheme'            => 'يجب أن يبدأ رابط Webhook بـ http:// أو https://.',
                        'connection_failed' => 'لا يمكن الوصول إلى رابط Webhook. يرجى التحقق من الرابط.',
                        'unreachable'       => 'رابط Webhook غير صالح (HTTP :code).',
                        'unsafe'            => 'يشير رابط Webhook إلى عنوان خاص أو محلي أو داخلي وغير مسموح به.',
                    ],
                    'success'    => 'تم حفظ إعدادات Webhook بنجاح',
                    'title'      => 'إعدادات Webhook',
                    'logs-title' => 'السجلات',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'المعرف',
                        'webhook'          => 'ويب هوك',
                        'sku'              => 'SKU',
                        'event'            => 'الحدث',
                        'created_at'       => 'التاريخ/الوقت',
                        'user'             => 'المستخدم',
                        'status'           => 'الحالة',
                        'success'          => 'ناجح',
                        'failed'           => 'فشل',
                        'server_error'     => 'خطأ في الخادم',
                        'timeout_or_error' => 'انتهاء المهلة/خطأ',
                        'delete'           => 'حذف',
                        'view'             => 'عرض',
                    ],
                    'title'          => 'سجلات Webhook',
                    'show-title'     => 'تفاصيل سجل Webhook',
                    'sent-payload'   => 'البيانات المرسلة',
                    'response'       => 'الاستجابة',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'لم يتم تسجيل أي بيانات لهذا السجل.',
                    'load-failed'    => 'فشل تحميل تفاصيل السجل.',
                    'delete-success' => 'تم حذف سجلات Webhook بنجاح',
                    'delete-failed'  => 'فشل حذف سجلات Webhook بشكل غير متوقع',
                    'unauthorized'   => 'هذا الإجراء غير مصرح به',
                ],
            ],
        ],
    ],
];
