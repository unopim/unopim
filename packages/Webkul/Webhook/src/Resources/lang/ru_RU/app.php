<?php

return [
    'components' => [
        'layouts' => [
            'sidebar' => [
                'menu' => [
                    'webhook' => [
                        'name' => 'Вебхуки',
                    ],
                ],
            ],
        ],
    ],
    'webhook-action' => [
        'delete-failed' => 'Пожалуйста, включите Webhook в настройках',
        'success'       => 'Данные продукта успешно отправлены в Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Вебхук',
        ],
        'settings' => [
            'index'  => 'Настройки',
            'update' => 'Обновить настройки',
        ],
        'logs' => [
            'index'       => 'Журналы',
            'delete'      => 'Удалить',
            'mass-delete' => 'Массовое удаление',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Настройки',
                    'title'   => 'Настройки Webhook',
                    'save'    => 'Сохранить',
                    'general' => 'Общие',
                    'active'  => [
                        'label' => 'Активный Webhook',
                    ],
                    'webhook_url' => [
                        'label' => 'URL Webhook',
                    ],
                    'success'    => 'Настройки Webhook успешно сохранены',
                    'logs-title' => 'Журналы',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Дата/Время',
                        'user'       => 'Пользователь',
                        'status'     => 'Статус',
                        'success'    => 'Успешно',
                        'failed'     => 'Неудача',
                        'delete'     => 'Удалить',
                    ],
                    'title'          => 'Журналы Webhook',
                    'delete-success' => 'Журналы Webhook успешно удалены',
                    'delete-failed'  => 'Удаление журналов Webhook неожиданно завершилось неудачей',
                ],
            ],
        ],
    ],
];
