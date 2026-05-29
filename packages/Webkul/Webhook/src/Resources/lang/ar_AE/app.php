<?php

declare(strict_types=1);

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
            'index' => 'ويب هوك',
        ],
        'settings' => [
            'index'  => 'الإعدادات',
            'update' => 'تحديث الإعدادات',
        ],
        'logs' => [
            'index'       => 'السجلات',
            'delete'      => 'حذف',
            'mass-delete' => 'حذف جماعي',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'الإعدادات',
                    'title'   => 'إعدادات Webhook',
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
                    ],
                    'success'    => 'تم حفظ إعدادات Webhook بنجاح',
                    'logs-title' => 'السجلات',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'المعرف',
                        'sku'              => 'SKU',
                        'created_at'       => 'التاريخ/الوقت',
                        'user'             => 'المستخدم',
                        'status'           => 'الحالة',
                        'success'          => 'ناجح',
                        'failed'           => 'فشل',
                        'server_error'     => 'خطأ في الخادم',
                        'timeout_or_error' => 'انتهاء المهلة/خطأ',
                        'delete'           => 'حذف',
                    ],
                    'title'          => 'سجلات Webhook',
                    'delete-success' => 'تم حذف سجلات Webhook بنجاح',
                    'delete-failed'  => 'فشل حذف سجلات Webhook بشكل غير متوقع',
                ],
            ],
        ],
    ],
];
