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
        'delete-failed' => 'Будь ласка, увімкніть Webhook у налаштуваннях',
        'success'       => 'Дані продукту успішно надіслано до Webhook',
    ],
    'acl' => [
        'webhook' => [
            'index' => 'Вебхук',
        ],
        'settings' => [
            'index'  => 'Налаштування',
            'update' => 'Оновити налаштування',
        ],
        'logs' => [
            'index'       => 'Журнали',
            'view'        => 'Переглянути',
            'delete'      => 'Видалити',
            'mass-delete' => 'Масове видалення',
        ],
    ],
    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Налаштування',
                    'title'   => 'Налаштування Webhook',
                    'save'    => 'Зберегти',
                    'general' => 'Загальні',
                    'active'  => [
                        'label' => 'Активний Webhook',
                    ],
                    'webhook_url' => [
                        'label'             => 'URL Webhook',
                        'required'          => 'URL Webhook є обов’язковим, коли Webhook активний.',
                        'scheme'            => 'URL Webhook повинен починатися з http:// або https://.',
                        'connection_failed' => 'Не вдалося підключитися до URL Webhook. Будь ласка, перевірте URL.',
                        'unreachable'       => 'URL Webhook недійсний (HTTP :code).',
                        'unsafe'            => 'URL вебхука вказує на приватну, петлеву або внутрішню адресу і не дозволений.',
                    ],
                    'success'    => 'Налаштування Webhook успішно збережено',
                    'logs-title' => 'Журнали',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'sku'              => 'SKU',
                        'created_at'       => 'Дата/Час',
                        'user'             => 'Користувач',
                        'status'           => 'Статус',
                        'success'          => 'Успішно',
                        'failed'           => 'Невдача',
                        'server_error'     => 'Помилка сервера',
                        'timeout_or_error' => 'Тайм-аут/Помилка',
                        'delete'           => 'Видалити',
                        'view'             => 'Переглянути',
                    ],
                    'title'          => 'Журнали Webhook',
                    'show-title'     => 'Деталі журналу Webhook',
                    'sent-payload'   => 'Надіслані дані',
                    'response'       => 'Відповідь',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Для цього журналу не записано жодних даних.',
                    'delete-success' => 'Журнали Webhook успішно видалено',
                    'delete-failed'  => 'Видалення журналів Webhook несподівано завершилося невдачею',
                    'unauthorized'   => 'Ця дія не авторизована',
                ],
            ],
        ],
    ],
];
