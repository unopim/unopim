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
            'index'  => 'Вебхук',
            'create' => 'Створити',
            'edit'   => 'Редагувати',
            'delete' => 'Видалити',
        ],
        'logs' => [
            'index'       => 'Журнали',
            'view'        => 'Переглянути',
            'delete'      => 'Видалити',
            'mass-delete' => 'Масове видалення',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Продукт створено',
            'updated' => 'Продукт оновлено',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Вебхуки',
            'create-btn'   => 'Створити вебхук',
            'logs-btn'     => 'Журнали',
            'back-btn'     => 'Назад до вебхуків',
            'default-name' => 'За замовчуванням',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Назва',
                'url'        => 'URL',
                'events'     => 'Події',
                'status'     => 'Статус',
                'active'     => 'Активний',
                'inactive'   => 'Неактивний',
                'created_at' => 'Створено',
                'edit'       => 'Редагувати',
                'delete'     => 'Видалити',
            ],
        ],
        'create' => [
            'title'    => 'Створити вебхук',
            'save-btn' => 'Зберегти',
        ],
        'edit' => [
            'title'    => 'Редагувати вебхук',
            'save-btn' => 'Зберегти',
        ],
        'form' => [
            'general'       => 'Загальні',
            'name'          => 'Назва',
            'url'           => 'URL',
            'events'        => 'Події',
            'select-events' => 'Виберіть події',
            'secret'        => 'Секрет підпису',
            'secret-set'    => 'Секрет уже встановлено',
            'secret-hint'   => 'Використовується для підпису кожного запиту за допомогою підпису HMAC SHA-256. Залиште порожнім, щоб зберегти поточний секрет.',
            'settings'      => 'Налаштування',
            'active'        => 'Активний',
            'test'          => 'Перевірка з’єднання',
            'test-hint'     => 'Надіслати тестовий запит на вказаний вище URL.',
            'test-btn'      => 'Надіслати тест',
            'test-no-url'   => 'Будь ласка, спочатку введіть URL.',
            'test-failed'   => 'Тестовий запит не вдався.',
            'headers'       => 'Користувацькі заголовки',
            'add-header'    => 'Додати заголовок',
            'no-headers'    => 'Користувацькі заголовки не додано.',
            'header-key'    => 'Заголовок',
            'header-value'  => 'Значення',
        ],
        'create-success' => 'Вебхук успішно створено',
        'update-success' => 'Вебхук успішно оновлено',
        'delete-success' => 'Вебхук успішно видалено',
        'delete-failed'  => 'Не вдалося видалити вебхук',
        'validation'     => [
            'unsafe-url' => 'URL вказує на приватну, петлеву або внутрішню адресу і не дозволений.',
            'scheme'     => 'URL повинен починатися з http:// або https://.',
        ],
        'test' => [
            'payload-message'   => 'Тестовий запит вебхука Unopim',
            'connection-failed' => 'Не вдалося зв’язатися з URL. Будь ласка, перевірте URL.',
            'unreachable'       => 'URL недоступний (HTTP :code).',
            'reachable'         => 'URL доступний.',
        ],
        'prune' => [
            'disabled' => 'Зберігання журналів вебхуків вимкнено; нічого не видалено.',
            'done'     => 'Видалено :count журнал(ів) вебхуків, старших за :days дн.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Вебхук',
                        'sku'              => 'SKU',
                        'event'            => 'Подія',
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
                    'load-failed'    => 'Не вдалося завантажити деталі журналу.',
                    'delete-success' => 'Журнали Webhook успішно видалено',
                    'delete-failed'  => 'Видалення журналів Webhook несподівано завершилося невдачею',
                    'unauthorized'   => 'Ця дія не авторизована',
                ],
            ],
        ],
    ],
];
