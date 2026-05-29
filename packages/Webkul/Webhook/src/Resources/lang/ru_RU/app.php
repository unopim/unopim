<?php

declare(strict_types=1);

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
                        'label'             => 'URL Webhook',
                        'required'          => 'URL Webhook обязателен, когда Webhook активен.',
                        'scheme'            => 'URL Webhook должен начинаться с http:// или https://.',
                        'connection_failed' => 'Не удалось подключиться к URL Webhook. Пожалуйста, проверьте URL.',
                        'unreachable'       => 'URL Webhook недействителен (HTTP :code).',
                        'unsafe'            => 'URL вебхука указывает на частный, петлевой или внутренний адрес и не разрешён.',
                    ],
                    'success'    => 'Настройки Webhook успешно сохранены',
                    'logs-title' => 'Журналы',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Дата/Время',
                        'user'             => 'Пользователь',
                        'status'           => 'Статус',
                        'success'          => 'Успешно',
                        'failed'           => 'Неудача',
                        'server_error'     => 'Ошибка сервера',
                        'timeout_or_error' => 'Тайм-аут/Ошибка',
                        'delete'           => 'Удалить',
                    ],
                    'title'          => 'Журналы Webhook',
                    'delete-success' => 'Журналы Webhook успешно удалены',
                    'delete-failed'  => 'Удаление журналов Webhook неожиданно завершилось неудачей',
                ],
            ],
        ],
    ],
];
