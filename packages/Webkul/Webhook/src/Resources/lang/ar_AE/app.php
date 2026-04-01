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
                        'label' => 'رابط Webhook',
                    ],
                    'success'    => 'تم حفظ إعدادات Webhook بنجاح',
                    'logs-title' => 'السجلات',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'المعرف',
                        'sku'        => 'SKU',
                        'created_at' => 'التاريخ/الوقت',
                        'user'       => 'المستخدم',
                        'status'     => 'الحالة',
                        'success'    => 'ناجح',
                        'failed'     => 'فشل',
                        'delete'     => 'حذف',
                    ],
                    'title'          => 'سجلات Webhook',
                    'delete-success' => 'تم حذف سجلات Webhook بنجاح',
                    'delete-failed'  => 'فشل حذف سجلات Webhook بشكل غير متوقع',
                ],
            ],
        ],
    ],
];
