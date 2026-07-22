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
            'index'  => 'Вебхук',
            'create' => 'Создать',
            'edit'   => 'Редактировать',
            'delete' => 'Удалить',
        ],
        'settings' => [
            'index'  => 'Настройки',
            'update' => 'Обновить настройки',
        ],
        'logs' => [
            'index'       => 'Журналы',
            'view'        => 'Просмотр',
            'delete'      => 'Удалить',
            'mass-delete' => 'Массовое удаление',
        ],
    ],

    'events' => [
        'product' => [
            'created' => 'Продукт создан',
            'updated' => 'Продукт обновлён',
        ],
    ],

    'webhooks' => [
        'index' => [
            'title'        => 'Вебхуки',
            'create-btn'   => 'Создать вебхук',
            'logs-btn'     => 'Журналы',
            'back-btn'     => 'Назад к вебхукам',
            'default-name' => 'По умолчанию',
            'datagrid'     => [
                'id'         => 'ID',
                'name'       => 'Название',
                'url'        => 'URL',
                'events'     => 'События',
                'status'     => 'Статус',
                'active'     => 'Активен',
                'inactive'   => 'Неактивен',
                'created_at' => 'Создано',
                'edit'       => 'Редактировать',
                'delete'     => 'Удалить',
            ],
        ],
        'create' => [
            'title'    => 'Создать вебхук',
            'cancel'   => 'Отмена',
            'save-btn' => 'Сохранить',
        ],
        'edit' => [
            'title'    => 'Редактировать вебхук',
            'cancel'   => 'Отмена',
            'save-btn' => 'Сохранить',
        ],
        'form' => [
            'general'       => 'Общие',
            'name'          => 'Название',
            'url'           => 'URL',
            'events'        => 'События',
            'select-events' => 'Выберите события',
            'secret'        => 'Секрет подписи',
            'secret-set'    => 'Секрет уже установлен',
            'secret-hint'   => 'Используется для подписи каждого запроса с помощью подписи HMAC SHA-256. Оставьте пустым, чтобы сохранить текущий секрет.',
            'settings'      => 'Настройки',
            'active'        => 'Активен',
            'test'          => 'Проверка соединения',
            'test-hint'     => 'Отправить тестовый запрос на указанный выше URL.',
            'test-btn'      => 'Отправить тест',
            'test-no-url'   => 'Пожалуйста, сначала введите URL.',
            'test-failed'   => 'Тестовый запрос не удался.',
            'headers'       => 'Пользовательские заголовки',
            'add-header'    => 'Добавить заголовок',
            'no-headers'    => 'Пользовательские заголовки не добавлены.',
            'header-key'    => 'Заголовок',
            'header-value'  => 'Значение',
        ],
        'create-success' => 'Вебхук успешно создан',
        'update-success' => 'Вебхук успешно обновлён',
        'delete-success' => 'Вебхук успешно удалён',
        'delete-failed'  => 'Не удалось удалить вебхук',
        'validation'     => [
            'unsafe-url' => 'URL указывает на частный, петлевой или внутренний адрес и не разрешён.',
            'scheme'     => 'URL должен начинаться с http:// или https://.',
        ],
        'test' => [
            'payload-message'   => 'Тестовый запрос вебхука Unopim',
            'connection-failed' => 'Не удалось связаться с URL. Пожалуйста, проверьте URL.',
            'unreachable'       => 'URL недоступен (HTTP :code).',
            'reachable'         => 'URL доступен.',
        ],
        'prune' => [
            'disabled' => 'Хранение журналов вебхуков отключено; ничего не удалено.',
            'done'     => 'Удалено :count журнал(ов) вебхуков старше :days дн.',
        ],
    ],

    'configuration' => [
        'webhook' => [
            'settings' => [
                'index' => [
                    'name'    => 'Настройки',
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
                    'title'      => 'Настройки Webhook',
                    'logs-title' => 'Журналы',
                ],
            ],

            'logs' => [
                'index' => [
                    'datagrid' => [
                        'id'               => 'ID',
                        'webhook'          => 'Вебхук',
                        'sku'              => 'SKU',
                        'event'            => 'Событие',
                        'created_at'       => 'Дата/Время',
                        'user'             => 'Пользователь',
                        'status'           => 'Статус',
                        'success'          => 'Успешно',
                        'failed'           => 'Неудача',
                        'server_error'     => 'Ошибка сервера',
                        'timeout_or_error' => 'Тайм-аут/Ошибка',
                        'delete'           => 'Удалить',
                        'view'             => 'Просмотр',
                    ],
                    'title'          => 'Журналы Webhook',
                    'show-title'     => 'Подробности лога Webhook',
                    'sent-payload'   => 'Отправленные данные',
                    'response'       => 'Ответ',
                    'back'           => 'Back to Logs',
                    'no-payload'     => 'Для этого журнала не записаны данные.',
                    'load-failed'    => 'Не удалось загрузить детали журнала.',
                    'delete-success' => 'Журналы Webhook успешно удалены',
                    'delete-failed'  => 'Удаление журналов Webhook неожиданно завершилось неудачей',
                    'unauthorized'   => 'Это действие не авторизовано',
                ],
            ],
        ],
    ],
];
