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
                        'label' => 'URL Webhook',
                    ],
                    'success'    => 'Налаштування Webhook успішно збережено',
                    'logs-title' => 'Журнали',
                ],
            ],
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'         => 'ID',
                        'sku'        => 'SKU',
                        'created_at' => 'Дата/Час',
                        'user'       => 'Користувач',
                        'status'     => 'Статус',
                        'success'    => 'Успішно',
                        'failed'     => 'Невдача',
                        'delete'     => 'Видалити',
                    ],
                    'title'          => 'Журнали Webhook',
                    'delete-success' => 'Журнали Webhook успішно видалено',
                    'delete-failed'  => 'Видалення журналів Webhook несподівано завершилося невдачею',
                ],
            ],
        ],
    ],
];
