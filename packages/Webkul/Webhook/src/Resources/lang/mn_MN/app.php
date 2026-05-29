<?php

declare(strict_types=1);

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Вебхууд',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Тохиргооноос Webhook-г идэвхжүүлнэ үү',
        'success'       => 'Бүтээгдэхүүний мэдээлэл Webhook руу амжилттай илгээгдлээ',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Вебхууд',
        ],
        'settings' => [
            'index'  => 'Тохиргоо',
            'update' => 'Тохиргоог шинэчлэх',
        ],
        'logs' => [
            'index'       => 'Логууд',
            'delete'      => 'Устгах',
            'mass-delete' => 'Бөөнөөр устгах',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Тохиргоо',
                    'title'   => 'Webhook тохиргоо',
                    'save'    => 'Хадгалах',
                    'general' => 'Ерөнхий',
                    'active'  => [
                        'label' => 'Идэвхтэй Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'Вебхуудын URL',
                        'required'          => 'Webhook идэвхтэй үед Webhook URL шаардлагатай.',
                        'scheme'            => 'Webhook URL нь http:// эсвэл https:// гэж эхэлсэн байх ёстой.',
                        'connection_failed' => 'Webhook URL-д хүрэх боломжгүй байна. URL-аа шалгана уу.',
                        'unreachable'       => 'Webhook URL хүчингүй байна (HTTP :code).',
                    ],
                    'success'    => 'Webhook тохиргоо амжилттай хадгалагдлаа',
                    'logs-title' => 'Логууд',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Огноо/Цаг',
                        'user'             => 'Хэрэглэгч',
                        'status'           => 'Төлөв',
                        'success'          => 'Амжилттай',
                        'failed'           => 'Амжилтгүй',
                        'server_error'     => 'Серверийн алдаа',
                        'timeout_or_error' => 'Хугацаа дууссан/Алдаа',
                        'delete'           => 'Устгах',
                    ],
                    'title'          => 'Webhook логууд',
                    'delete-success' => 'Webhook логууд амжилттай устгагдлаа',
                    'delete-failed'  => 'Webhook логуудыг устгах нь гэнэтийн алдаа гарлаа',
                ],
            ],
        ],
    ],
];
